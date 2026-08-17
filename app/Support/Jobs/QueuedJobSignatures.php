<?php

namespace App\Support\Jobs;

use App\Console\Commands\Maintenance\RefreshJobSignaturesCommand;
use Illuminate\Contracts\Queue\ShouldQueue;
use ReflectionClass;
use ReflectionParameter;
use Symfony\Component\Finder\Finder;

/**
 * A snapshot of every queued job's constructor, so that changing one has to be deliberate.
 *
 * A queued payload is a wire format between two versions of the panel. Convoy's long Proxmox
 * operations are modelled as jobs that re-enqueue themselves until the remote task finishes — a
 * clone polls itself for up to thirty minutes — so on any upgrade there is work in Valkey that was
 * serialised by the *old* release and will be executed by the *new* one. The queue cannot be
 * drained first; there is no moment when it is reliably empty.
 *
 * That crossing is safe today because the payloads are nearly stateless: `SerializesModels` stores
 * a class and an id, so models are re-fetched against the migrated schema, and the mutable state of
 * an operation lives on the `deployment_steps` row rather than in the payload. What breaks it is
 * changing the shape of the payload itself:
 *
 *  - Removing or renaming a job class strands its queued payloads — `Class not found`, failed
 *    permanently, work abandoned with no operator-visible cause.
 *  - Removing, renaming, reordering or retyping a constructor parameter means new code reads a
 *    property the old payload does not carry.
 *  - Adding a *promoted* parameter is the subtle one. `unserialize()` does not call the
 *    constructor, and a promoted parameter's default never reaches the class property table, so on
 *    an old payload the property is left uninitialized and throws on first access — the default you
 *    wrote is not what you get. New parameters must be declared in the class body with a default
 *    (typically `= null`) or backfilled in `__unserialize()`.
 *
 * Hence the rule this snapshot enforces: **for one release cycle, job classes and their
 * constructors are append-only.** To retire a job, stop dispatching it but keep the class for a
 * release, forwarding to whatever replaced it, and delete it in the next one.
 *
 * The snapshot is not a policy engine and does not try to be. It fails the build when a signature
 * moves; a human reads the diff and decides whether the change needs a compatibility shim. That
 * decision needs judgement about which release is upgrading from which, which is exactly the thing
 * a linter cannot supply.
 *
 * Regenerate with {@see RefreshJobSignaturesCommand} and review the diff before committing.
 */
final class QueuedJobSignatures
{
    /** The generated snapshot. Rewritten wholesale by the refresh command. */
    public const SNAPSHOT = __DIR__.'/queued-job-signatures.php';

    /** Where queued jobs live, relative to the project root. */
    public const DIRECTORY = 'app/Jobs';

    /** The namespace {@see self::DIRECTORY} maps onto. */
    private const NAMESPACE = 'App\\Jobs\\';

    /**
     * Every queued job's constructor as it exists in the working tree right now.
     *
     * @return array<class-string, list<array<string, bool|string>>>
     */
    public static function current(): array
    {
        $signatures = [];

        foreach (self::classes() as $class) {
            $signatures[$class] = self::parametersOf($class);
        }

        // Sorted so the snapshot's diff shows what changed rather than how the filesystem
        // happened to enumerate the directory that day.
        ksort($signatures);

        return $signatures;
    }

    /**
     * The snapshot as committed.
     *
     * @return array<class-string, list<array<string, bool|string>>>
     */
    public static function recorded(): array
    {
        // Absent only before the first generation; treating that as "nothing recorded" lets the
        // refresh command bootstrap the file instead of fataling on it.
        return is_file(self::SNAPSHOT) ? require self::SNAPSHOT : [];
    }

    /**
     * Every concrete queued job class under {@see self::DIRECTORY}.
     *
     * @return list<class-string>
     */
    private static function classes(): array
    {
        $classes = [];

        foreach (Finder::create()->files()->in(base_path(self::DIRECTORY))->name('*.php') as $file) {
            $class = self::NAMESPACE.str_replace(
                ['/', '.php'],
                ['\\', ''],
                $file->getRelativePathname(),
            );

            if (! class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            // Abstract bases and the middleware living alongside the jobs are never serialised
            // onto the queue, so their shape is nobody's compatibility problem.
            if ($reflection->isAbstract() || ! $reflection->implementsInterface(ShouldQueue::class)) {
                continue;
            }

            $classes[] = $class;
        }

        return $classes;
    }

    /**
     * @param  class-string  $class
     * @return list<array<string, bool|string>>
     */
    private static function parametersOf(string $class): array
    {
        $constructor = (new ReflectionClass($class))->getConstructor();

        if ($constructor === null) {
            return [];
        }

        return array_map(
            static fn (ReflectionParameter $parameter): array => [
                'name' => $parameter->getName(),
                'type' => $parameter->hasType() ? (string) $parameter->getType() : 'mixed',
                'optional' => $parameter->isOptional(),
                // Recorded because it changes what happens on an old payload: a promoted
                // parameter's default does not survive unserialize(), an ordinary property's does.
                'promoted' => $parameter->isPromoted(),
                'variadic' => $parameter->isVariadic(),
            ],
            $constructor->getParameters(),
        );
    }
}
