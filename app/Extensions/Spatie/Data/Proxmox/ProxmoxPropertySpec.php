<?php

namespace App\Extensions\Spatie\Data\Proxmox;

use LogicException;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Resolved metadata for one {@see ProxmoxProperty}-attributed DTO property:
 * the PVE key it maps to and how to convert its value in each direction.
 *
 * int, string, and backed-enum properties are handled automatically from the
 * declared type; anything else (notably PVE's 1/0 booleans) must supply an
 * explicit {@see ProxmoxPropertyCast} on the attribute.
 */
class ProxmoxPropertySpec
{
    /**
     * @param  class-string|null  $enumClass  Backed-enum class when the property is one.
     */
    public function __construct(
        public readonly string $property,
        public readonly string $key,
        private readonly ?ProxmoxPropertyCast $cast,
        private readonly ?string $enumClass,
        private readonly string $typeName,
    ) {}

    public static function fromParameter(ReflectionParameter $parameter, ProxmoxProperty $meta): self
    {
        $type = $parameter->getType();
        $typeName = $type instanceof ReflectionNamedType ? $type->getName() : 'string';

        $cast = $meta->cast !== null ? new $meta->cast() : null;
        $enumClass = null;

        if ($cast === null) {
            if (enum_exists($typeName)) {
                $enumClass = $typeName;
            } elseif ($typeName === 'bool') {
                throw new LogicException(sprintf(
                    'Proxmox bool property "%s" must declare a cast (e.g. PveBooleanCast); PVE encodes booleans as 1/0.',
                    $parameter->getName(),
                ));
            }
        }

        return new self($parameter->getName(), $meta->key, $cast, $enumClass, $typeName);
    }

    public function parse(string $value): mixed
    {
        if ($this->cast !== null) {
            return $this->cast->parse($value);
        }

        if ($this->enumClass !== null) {
            return ($this->enumClass)::from($value);
        }

        return match ($this->typeName) {
            'int' => (int) $value,
            'float' => (float) $value,
            default => $value,
        };
    }

    public function emit(mixed $value): ?string
    {
        if ($this->cast !== null) {
            return $this->cast->emit($value);
        }

        if ($this->enumClass !== null) {
            return $value->value;
        }

        return (string) $value;
    }
}
