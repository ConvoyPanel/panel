<?php

namespace Tests\Support;

use Closure;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * The console's rules for a report, copied from its StoreTelemetryReportRequest
 * and SlugMap (contract: docs/telemetry.md). A report that fails these would be
 * refused with a 422, so the panel's tests hold the builder to them.
 *
 * When the console's rules change, change these with them.
 */
final class TelemetryContract
{
    private const VERSION = '/^v?(\d+(?:\.\d+){1,3})/';

    private const KEY = '/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/';

    private const MAX_VALUE = 10_000_000;

    private const MAX_KEYS = 200;

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, list<string>> validation errors, empty when the console would accept it
     */
    public static function errors(array $report): array
    {
        $count = ['required', 'integer', 'min:0', 'max:10000000'];
        $version = ['string', 'max:64', 'regex:'.self::VERSION];

        return Validator::make($report, [
            'schema' => ['required', 'integer', Rule::in([1])],
            'installation' => ['required', 'uuid'],

            'panel' => ['required', 'array'],
            'panel.version' => ['required', 'string', 'max:64'],
            'panel.php' => ['required', ...$version],
            'panel.database' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9-]+$/'],
            'panel.database_version' => ['nullable', ...$version],

            'users' => ['required', 'array'],
            'users.total' => $count,
            'users.admins' => $count,
            'users.active' => ['required', 'array'],
            'users.active.day' => $count,
            'users.active.week' => $count,
            'users.active.month' => $count,
            'users.active_admins' => ['required', 'array'],
            'users.active_admins.day' => $count,
            'users.active_admins.week' => $count,
            'users.active_admins.month' => $count,

            'nodes' => ['present', 'list', 'max:500'],
            'nodes.*.proxmox' => ['nullable', ...$version],
            'nodes.*.agent' => ['nullable', ...$version],
            'nodes.*.cpus' => ['required', 'integer', 'min:0', 'max:10000'],
            'nodes.*.memory_gib' => ['required', 'integer', 'min:0', 'max:1000000'],
            'nodes.*.servers' => ['required', 'integer', 'min:0', 'max:100000'],

            'resources' => ['sometimes', self::slugMap()],
            'features' => ['sometimes', self::slugMap(booleans: true)],
            'activity' => ['sometimes', self::slugMap()],
            'views' => ['sometimes', self::slugMap()],
        ])->errors()->toArray();
    }

    private static function slugMap(bool $booleans = false): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($booleans) {
            if (! is_array($value) || ($value !== [] && array_is_list($value))) {
                $fail("{$attribute} must be an object.");

                return;
            }

            if (count($value) > self::MAX_KEYS) {
                $fail("{$attribute} may not have more than ".self::MAX_KEYS.' keys.');

                return;
            }

            foreach ($value as $key => $item) {
                if (! is_string($key) || strlen($key) > 120 || preg_match(self::KEY, $key) !== 1) {
                    $fail("{$attribute} key [{$key}] is not a lowercase slug.");

                    return;
                }

                if ($booleans ? ! is_bool($item) : ! is_int($item) || $item < 0 || $item > self::MAX_VALUE) {
                    $fail("{$attribute}.{$key} is not a ".($booleans ? 'boolean' : 'count').'.');

                    return;
                }
            }
        };
    }
}
