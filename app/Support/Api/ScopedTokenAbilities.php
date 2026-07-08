<?php

namespace App\Support\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Shared machinery for a resource-scoped API-token ability vocabulary. Abilities are split
 * read/write per top-level resource (`servers:read`, `nodes:write`, …), plus the wildcards `*`
 * (everything) and `{resource}:*`.
 *
 * Concrete vocabularies declare their resource set and the API path prefix they sit behind:
 * {@see TokenAbilities} for the admin/application API and {@see AccountTokenAbilities} for the
 * end-user (client) API.
 *
 * Scoping is coarse (top-level resource): a token with `nodes:read` can read a node's sub-resources
 * (network interfaces, storages, isos) too. Write implies read for the same resource.
 */
abstract class ScopedTokenAbilities
{
    /**
     * Top-level resources of this API (the first path segment after {@see static::PATH_PREFIX}).
     *
     * @var list<string>
     */
    public const RESOURCES = [];

    public const ACTIONS = ['read', 'write'];

    /** The path prefix stripped before reading the resource segment (e.g. `api/application/`). */
    protected const PATH_PREFIX = '';

    /**
     * Every valid ability string, for validating token creation. Includes `*`, `{resource}:*`, and
     * `{resource}:{read|write}`.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        $abilities = ['*'];

        foreach (static::RESOURCES as $resource) {
            $abilities[] = "{$resource}:*";
            foreach (static::ACTIONS as $action) {
                $abilities[] = "{$resource}:{$action}";
            }
        }

        return $abilities;
    }

    /**
     * The ability a request requires. Unknown resources require `*`, so a scoped token can never
     * reach an endpoint that isn't explicitly in the vocabulary.
     */
    public static function requiredFor(Request $request): string
    {
        $path = Str::of($request->path())->after(static::PATH_PREFIX)->trim('/');
        $resource = $path->before('/')->toString();
        $action = in_array($request->method(), ['GET', 'HEAD'], true) ? 'read' : 'write';

        if (! in_array($resource, static::RESOURCES, true)) {
            return '*';
        }

        return "{$resource}:{$action}";
    }

    /**
     * Whether a set of granted abilities satisfies the required one. `*` grants everything,
     * `{resource}:*` grants both actions, and `{resource}:write` implies `{resource}:read`.
     *
     * @param  list<string>  $granted
     */
    public static function grants(array $granted, string $required): bool
    {
        if (in_array('*', $granted, true) || in_array($required, $granted, true)) {
            return true;
        }

        if (! str_contains($required, ':')) {
            return false;
        }

        [$resource, $action] = explode(':', $required, 2);

        if (in_array("{$resource}:*", $granted, true)) {
            return true;
        }

        // Write access implies read access for the same resource.
        return $action === 'read' && in_array("{$resource}:write", $granted, true);
    }
}
