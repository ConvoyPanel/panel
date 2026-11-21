<?php

namespace App\Support\OpenApi;

use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Support\Generator\ClassBasedReference;
use Dedoc\Scramble\Support\Generator\Combined\AnyOf;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types as OpenApi;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/**
 * Describes a spatie/laravel-data object the way it goes over the wire.
 *
 * Every response in this API is a Data object, and Scramble's own support for them is a paid
 * add-on, so without this the reference would show `{}` for nearly every endpoint. Read from the
 * promoted constructor properties, which is what laravel-data serialises:
 *
 * - `Optional` and `Lazy` members make a property optional, since they are left out unless set
 *   or loaded.
 * - `array` takes its items from `#[DataCollectionOf]` or a `@var list<T>` docblock.
 * - Nested Data objects and enums become `$ref`s to their own components.
 * - A Data object returned from a controller is wrapped in `data`, per `config/data.php`.
 */
class LaravelDataToSchema extends TypeToSchemaExtension
{
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof ObjectType
            && class_exists($type->name)
            && is_a($type->name, Data::class, true);
    }

    /** @param  ObjectType  $type */
    public function toSchema(Type $type): OpenApi\Type
    {
        $class = new ReflectionClass($type->name);
        $schema = new OpenApi\ObjectType;
        $snakeCase = $this->outputsSnakeCase($class);

        foreach ($class->getConstructor()?->getParameters() ?? [] as $parameter) {
            if (! $parameter->isPromoted()) {
                continue;
            }

            $property = $class->getProperty($parameter->getName());
            [$propertySchema, $optional] = $this->propertySchema($class, $property);
            $name = $this->outputName($property, $snakeCase);

            $schema->addProperty($name, $propertySchema);

            if (! $optional) {
                $schema->addRequired([$name]);
            }
        }

        return $schema;
    }

    public function reference(ObjectType $type): Reference
    {
        return ClassBasedReference::create('schemas', $type->name, $this->components);
    }

    public function toResponse(Type $type): Response
    {
        $wrapped = (new OpenApi\ObjectType)
            ->addProperty((string) config('data.wrap', 'data'), $this->openApiTransformer->transform($type))
            ->setRequired([(string) config('data.wrap', 'data')]);

        return Response::make(200)->setContent('application/json', Schema::fromType($wrapped));
    }

    /** @return array{OpenApi\Type, bool} the schema, and whether the property may be absent */
    private function propertySchema(ReflectionClass $class, ReflectionProperty $property): array
    {
        $reflected = $property->getType();
        $members = $reflected instanceof ReflectionUnionType ? $reflected->getTypes() : [$reflected];
        $nullable = $reflected?->allowsNull() ?? true;
        $optional = false;
        $schemas = [];

        foreach ($members as $member) {
            if (! $member instanceof ReflectionNamedType) {
                $schemas[] = new OpenApi\MixedType;

                continue;
            }

            $name = $member->getName();

            if (is_a($name, Optional::class, true) || is_a($name, Lazy::class, true)) {
                $optional = true;

                continue;
            }

            if ($name === 'null') {
                continue;
            }

            $schemas[] = $this->schemaFor($class, $property, $name);
        }

        $schema = match (count($schemas)) {
            0 => new OpenApi\MixedType,
            1 => $schemas[0],
            default => (new AnyOf)->setItems($schemas),
        };

        if ($nullable && ! $schema instanceof OpenApi\MixedType) {
            $schema->nullable(true);
        }

        if ($description = $this->description($property)) {
            $schema->setDescription($description);
        }

        return [$schema, $optional];
    }

    private function schemaFor(ReflectionClass $class, ReflectionProperty $property, string $name): OpenApi\Type
    {
        return match (true) {
            $name === 'int' => new OpenApi\IntegerType,
            $name === 'float' => new OpenApi\NumberType,
            $name === 'string' => new OpenApi\StringType,
            $name === 'bool' => new OpenApi\BooleanType,
            $name === 'array', is_a($name, DataCollection::class, true) => (new OpenApi\ArrayType)
                ->setItems($this->itemSchema($class, $property)),
            class_exists($name) || enum_exists($name) => $this->openApiTransformer->transform(new ObjectType($name)),
            default => new OpenApi\MixedType,
        };
    }

    private function itemSchema(ReflectionClass $class, ReflectionProperty $property): OpenApi\Type
    {
        foreach ($property->getAttributes(DataCollectionOf::class) as $attribute) {
            return $this->openApiTransformer->transform(new ObjectType($attribute->newInstance()->class));
        }

        // `@var list<string>`, `@var array<int, ServerPermission>` and the like.
        if (! preg_match('/@var\s+(?:list|array|non-empty-list)<(?:[^,>]+,\s*)?\\\\?([\w\\\\]+)>/', (string) $property->getDocComment(), $matches)) {
            return new OpenApi\MixedType;
        }

        $item = $matches[1];

        return match ($item) {
            'int', 'integer' => new OpenApi\IntegerType,
            'float' => new OpenApi\NumberType,
            'string' => new OpenApi\StringType,
            'bool', 'boolean' => new OpenApi\BooleanType,
            default => ($resolved = $this->resolveClass($class, $item)) !== null
                ? $this->openApiTransformer->transform(new ObjectType($resolved))
                : new OpenApi\MixedType,
        };
    }

    /** A class named in a docblock, resolved against the file's imports and namespace. */
    private function resolveClass(ReflectionClass $class, string $name): ?string
    {
        if (class_exists($name) || enum_exists($name)) {
            return $name;
        }

        $source = (string) file_get_contents((string) $class->getFileName());

        if (preg_match('/^use\s+([\w\\\\]+\\\\'.preg_quote($name, '/').');/m', $source, $import)) {
            return $import[1];
        }

        $sibling = $class->getNamespaceName().'\\'.$name;

        return class_exists($sibling) || enum_exists($sibling) ? $sibling : null;
    }

    private function outputsSnakeCase(ReflectionClass $class): bool
    {
        foreach ([...$class->getAttributes(MapOutputName::class), ...$class->getAttributes(MapName::class)] as $attribute) {
            $arguments = $attribute->getArguments();
            $output = $arguments['output'] ?? $arguments[1] ?? $arguments['input'] ?? $arguments[0] ?? null;

            if ($output === SnakeCaseMapper::class) {
                return true;
            }
        }

        return false;
    }

    private function outputName(ReflectionProperty $property, bool $snakeCase): string
    {
        foreach ([...$property->getAttributes(MapOutputName::class), ...$property->getAttributes(MapName::class)] as $attribute) {
            $arguments = $attribute->getArguments();
            $output = $arguments['output'] ?? $arguments[1] ?? $arguments['input'] ?? $arguments[0] ?? null;

            if (is_string($output) && ! class_exists($output)) {
                return $output;
            }
        }

        return $snakeCase ? (new SnakeCaseMapper)->map($property->getName()) : $property->getName();
    }

    /** The first line of prose in the property's docblock, if it has one. */
    private function description(ReflectionProperty $property): ?string
    {
        $doc = preg_replace(['#^\s*/\*\*#', '#\*/\s*$#'], '', (string) $property->getDocComment());
        $lines = array_map(fn (string $line) => trim(ltrim(trim($line), '*')), explode("\n", (string) $doc));
        $prose = array_filter($lines, fn (string $line) => $line !== '' && ! str_starts_with($line, '@'));

        return $prose === [] ? null : implode(' ', $prose);
    }
}
