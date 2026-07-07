<?php

namespace App\Support\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * The scoped-ability vocabulary for API tokens. Abilities are resource-scoped and split read/write:
 * `servers:read`, `nodes:write`, …, plus the wildcards `*` (everything) and `{resource}:*`.
 *
 * Scoping is coarse (top-level resource): a token with `nodes:read` can read a node's sub-resources
 * (network interfaces, storages, isos) too. Write implies read for the same resource.
 */
final class TokenAbilities
{
    /** Top-level resources of the application API (the first path segment). */
    public const RESOURCES = [
        'overview',
        'locations',
        'nodes',
        'servers',
        'address-block-groups',
        'template-groups',
        'users',
        'coterms',
    ];

    public const ACTIONS = ['read', 'write'];

    /**
     * Every valid ability string, for validating token creation. Includes `*`, `{resource}:*`, and
     * `{resource}:{read|write}`.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        $abilities = ['*'];

        foreach (self::RESOURCES as $resource) {
            $abilities[] = "{$resource}:*";
            foreach (self::ACTIONS as $action) {
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
        $path = Str::of($request->path())->after('api/application/')->trim('/');
        $resource = $path->before('/')->toString();
        $action = in_array($request->method(), ['GET', 'HEAD'], true) ? 'read' : 'write';

        if (! in_array($resource, self::RESOURCES, true)) {
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
