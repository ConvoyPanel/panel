<?php

namespace App\Support\Anchor;

use App\Jobs\Server\MigrateVmJob;

/**
 * The migration half of the Anchor wire contract, in one place.
 *
 * docs/migration-anchor-contract.md fixes the protocol members and the
 * ordering but names only one URL, `GET /artifacts/{artifact}`. The control
 * plane paths below are this panel's half of that contract; they follow the
 * shape the install pipeline already shipped
 * (`POST /api/v1/templates/installs`, `GET|DELETE .../{id}`) so a reader of
 * either side recognises the other. They are constants rather than literals
 * because a rename has to be one edit on each side, not a grep.
 */
final class AnchorMigrationProtocol
{
    /** Queue a `vzdump` of one guest. Answers with a job. */
    public const EXPORTS = '/api/v1/templates/exports';

    /**
     * Poll (GET) or cancel (DELETE) one export job.
     *
     * A different path from an install job's, although both are jobs in one
     * registry on the agent. The panel therefore has to know which side it is
     * asking about rather than holding a single "poll this job id" call.
     */
    public const EXPORT = '/api/v1/templates/jobs/{job}';

    /** Delete one finished artifact. */
    public const ARTIFACT = '/api/v1/templates/artifacts/{artifact}';

    /**
     * Where the destination downloads the archive from. At the root and
     * deliberately not under `/api/v1`: it is the one endpoint a party other
     * than the panel talks to.
     *
     * Single `Range` requests answer 206 and are what the install side's retry
     * resumes with. A multi-range request answers 200 with the whole body, so
     * nothing here asks for one.
     */
    public const FETCH = '/artifacts/{artifact}';

    /** The install pipeline, reused unchanged. */
    public const INSTALLS = '/api/v1/templates/installs';

    /** Poll (GET) or cancel (DELETE) one install job. */
    public const INSTALL = '/api/v1/templates/installs/{job}';

    /**
     * A source node must be able to dump and serve a guest.
     *
     * Not advertised by any shipped Anchor yet, which is the point: refusing a
     * migration the agent would reject is better than queueing one, and better
     * still than the failure this guards against. The install pipeline
     * finishes by running `qm template` on what it restored, so an agent that
     * does not know it is servicing a migration turns the migrated guest into
     * a template.
     */
    public const EXPORT_CAPABILITY = 'migration.export';

    /** A destination node must be able to restore a migration artifact as a guest. */
    public const INSTALL_CAPABILITY = 'migration.install';

    /**
     * How long an artifact download token is good for.
     *
     * It has to outlive the transfer itself, because the destination holds one
     * token for the whole download and `Range` resumption reuses it. Six hours
     * matches what {@see MigrateVmJob} already allows a
     * storage migration of a large local disk.
     */
    public const ARTIFACT_TOKEN_MINUTES = 360;

    /** Control-plane calls are small and local; nothing here is a transfer. */
    public const TIMEOUT_SECONDS = 15;

    public static function url(string $base, string $path, array $replace = []): string
    {
        foreach ($replace as $key => $value) {
            $path = str_replace('{'.$key.'}', rawurlencode((string) $value), $path);
        }

        return rtrim($base, '/').$path;
    }
}
