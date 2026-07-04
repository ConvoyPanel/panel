<?php

namespace App\Extensions\Spatie\Data\Proxmox;

use Illuminate\Support\Arr;
use ReflectionClass;

/**
 * Drives the {@see ProxmoxProperty} attribute for a DTO: turns the `key=value`
 * tail of a Proxmox property list into typed constructor arguments, and back
 * again. This replaces the hand-written `isset(...)` parse ladders and
 * `if ($x !== null)` emit blocks the config DTOs used to carry, and derives the
 * set of "known" keys automatically so unmodeled keys can be preserved without
 * maintaining a duplicate exclusion list.
 *
 * The positional head of the property list (e.g. `virtio=<mac>` or the disk
 * volume) is not covered here — it is DTO-specific and handled by the DTO.
 */
trait MapsProxmoxProperties
{
    /** @var array<class-string, list<ProxmoxPropertySpec>> */
    private static array $proxmoxSpecCache = [];

    /**
     * Parse the `key=value` tail into a map of DTO property name => typed value,
     * alongside the leftover pairs we don't explicitly model.
     *
     * @param  array<string, string>  $pairs
     * @return array{0: array<string, mixed>, 1: array<string, string>} [mapped, leftover]
     */
    protected static function mapProxmoxProperties(array $pairs): array
    {
        $mapped = [];
        $known = [];

        foreach (self::proxmoxPropertySpecs() as $spec) {
            $known[] = $spec->key;

            if (array_key_exists($spec->key, $pairs)) {
                $mapped[$spec->property] = $spec->parse($pairs[$spec->key]);
            }
        }

        return [$mapped, Arr::except($pairs, $known)];
    }

    /**
     * Emit the modeled tail keys as PVE `key=value` pairs, skipping null (i.e.
     * unset) properties so partial updates never resend an empty value.
     *
     * @return array<string, string>
     */
    protected function toProxmoxProperties(): array
    {
        $pairs = [];

        foreach (self::proxmoxPropertySpecs() as $spec) {
            $value = $this->{$spec->property};

            if ($value === null) {
                continue;
            }

            $emitted = $spec->emit($value);

            if ($emitted !== null) {
                $pairs[$spec->key] = $emitted;
            }
        }

        return $pairs;
    }

    /**
     * @return list<ProxmoxPropertySpec>
     */
    private static function proxmoxPropertySpecs(): array
    {
        return self::$proxmoxSpecCache[static::class] ??= self::resolveProxmoxPropertySpecs();
    }

    /**
     * @return list<ProxmoxPropertySpec>
     */
    private static function resolveProxmoxPropertySpecs(): array
    {
        $constructor = (new ReflectionClass(static::class))->getConstructor();

        $specs = [];
        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            $attribute = $parameter->getAttributes(ProxmoxProperty::class)[0] ?? null;

            if ($attribute === null) {
                continue;
            }

            $specs[] = ProxmoxPropertySpec::fromParameter($parameter, $attribute->newInstance());
        }

        return $specs;
    }
}
