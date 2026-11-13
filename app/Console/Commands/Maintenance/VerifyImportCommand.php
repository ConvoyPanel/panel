<?php

namespace Convoy\Console\Commands\Maintenance;

use Convoy\Enums\Api\ApiKeyType;
use Convoy\Models\Node;
use Convoy\Models\PersonalAccessToken;
use Convoy\Models\Server;
use Convoy\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use Throwable;

/**
 * Checks a database that `convoy import-v4` brought over from MySQL, with the
 * things a clean `migrate` doesn't prove: the APP_KEY decrypts what v4 encrypted,
 * every model loads, and API tokens issued under v4 still authenticate.
 *
 * It changes nothing. The token check has to store a known hash to make a
 * request with it (v4 kept only hashes), so it runs in a transaction that is
 * rolled back.
 */
class VerifyImportCommand extends Command
{
    protected $signature = 'import:verify';

    protected $description = 'Check that a database imported from a v4 install works on this version.';

    private int $failures = 0;

    public function handle(HttpKernel $http): int
    {
        $this->check('every migration has run', function () {
            /** @var Migrator $migrator */
            $migrator = app('migrator');
            $files = array_keys($migrator->getMigrationFiles([database_path('migrations'), ...$migrator->paths()]));
            $pending = array_diff($files, $migrator->getRepository()->getRan());

            if ($pending !== []) {
                throw new \RuntimeException(count($pending).' pending, starting with '.reset($pending));
            }

            return count($files).' ran';
        });

        $this->check('node API token secrets decrypt with this APP_KEY', function () {
            $nodes = Node::all();
            $nodes->each(fn (Node $node) => $node->secret);

            return $nodes->count().' nodes';
        });

        $this->check('two-factor secrets decrypt with this APP_KEY', function () {
            $users = User::query()->whereNotNull('two_factor_secret')->get();
            $users->each(fn (User $user) => decrypt($user->two_factor_secret));

            return $users->count().' users';
        });

        foreach ($this->models() as $class) {
            $this->check('load every '.class_basename($class), fn () => $class::query()->get()->each->toArray()->count().' rows');
        }

        $this->check('load servers with their node, owner, addresses and backups', fn () => Server::with(['node', 'user', 'addresses', 'backups'])->get()->each->toArray()->count().' servers');

        $this->check('API token owners resolve', function () {
            $missing = PersonalAccessToken::all()->filter(fn (PersonalAccessToken $token) => is_null($token->tokenable));

            if ($missing->isNotEmpty()) {
                throw new \RuntimeException($missing->count().' tokens belong to no one: '.$missing->pluck('tokenable_type')->unique()->implode(', '));
            }

            return PersonalAccessToken::count().' tokens';
        });

        foreach ([
            ['/api/application/servers', ApiKeyType::APPLICATION],
            ['/api/application/nodes', ApiKeyType::APPLICATION],
            ['/api/application/locations', ApiKeyType::APPLICATION],
            ['/api/application/address-pools', ApiKeyType::APPLICATION],
        ] as [$uri, $type]) {
            $this->check("an existing {$type->value} token can GET {$uri}", fn () => $this->request($http, $uri, $type));
        }

        $this->newLine();

        if ($this->failures > 0) {
            $this->error("{$this->failures} ".str('check')->plural($this->failures).' failed.');

            return self::FAILURE;
        }

        $this->info('Everything checked out.');

        return self::SUCCESS;
    }

    private function check(string $label, callable $check): void
    {
        try {
            $detail = $check();
            $this->line("  <fg=green>✓</> {$label}".($detail ? " <fg=gray>({$detail})</>" : ''));
        } catch (Throwable $e) {
            $this->failures++;
            $this->line("  <fg=red>✗</> {$label}");
            $this->line('    '.class_basename($e).': '.str($e->getMessage())->limit(300));

            if ($e instanceof DecryptException) {
                $this->line('    <fg=yellow>APP_KEY is not the key this data was encrypted with. Copy APP_KEY from the v4 .env unchanged.</>');
            }
        }
    }

    /** Calls the API as the oldest token of a type, the way an integration holding it would. */
    private function request(HttpKernel $http, string $uri, ApiKeyType $type): ?string
    {
        $token = PersonalAccessToken::query()->where('type', $type->value)->orderBy('id')->first();

        if (is_null($token)) {
            return "no {$type->value} tokens to try";
        }

        DB::beginTransaction();

        try {
            // Only the hash is stored, so give the token a known secret for this request.
            DB::table('personal_access_tokens')->where('id', $token->id)->update([
                'token' => hash('sha256', $secret = 'import-verify'),
            ]);

            $response = $http->handle(Request::create($uri, 'GET', server: [
                'HTTP_AUTHORIZATION' => "Bearer {$token->id}|{$secret}",
                'HTTP_ACCEPT' => 'application/json',
            ]));
        } finally {
            DB::rollBack();
            app()->forgetInstance('request');
            auth()->forgetGuards();
        }

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException("answered {$response->getStatusCode()}: ".str($response->getContent())->squish()->limit(200));
        }

        return "token {$token->id}";
    }

    /** @return list<class-string<Model>> */
    private function models(): array
    {
        return collect(glob(app_path('Models/*.php')))
            ->map(fn (string $file) => 'Convoy\\Models\\'.basename($file, '.php'))
            ->filter(fn (string $class) => class_exists($class)
                && is_subclass_of($class, Model::class)
                && ! (new ReflectionClass($class))->isAbstract())
            ->values()
            ->all();
    }
}
