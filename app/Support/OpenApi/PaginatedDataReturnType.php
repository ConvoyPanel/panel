<?php

namespace App\Support\OpenApi;

use App\Data\PaginationMeta;
use Dedoc\Scramble\Infer\Extensions\Event\StaticMethodCallEvent;
use Dedoc\Scramble\Infer\Extensions\StaticMethodReturnTypeExtension;
use Dedoc\Scramble\Support\Type\ArrayItemType_;
use Dedoc\Scramble\Support\Type\ArrayType;
use Dedoc\Scramble\Support\Type\GenericClassStringType;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\Literal\LiteralStringType;
use Dedoc\Scramble\Support\Type\MixedType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;

/**
 * `PaginationMeta::paginate($paginator, ServerData::class)` is `{items: ServerData[], pagination}`.
 *
 * The class is a runtime argument, which inference cannot follow on its own, so every list
 * endpoint came out as `items: string`.
 */
class PaginatedDataReturnType implements StaticMethodReturnTypeExtension
{
    public function shouldHandle(string $name): bool
    {
        return $name === PaginationMeta::class;
    }

    public function getStaticMethodReturnType(StaticMethodCallEvent $event): ?Type
    {
        if ($event->getName() !== 'paginate') {
            return null;
        }

        $class = $event->getArg('dataClass', 1);
        $name = match (true) {
            $class instanceof LiteralStringType => $class->value,
            $class instanceof GenericClassStringType => $class->type->name ?? null,
            default => null,
        };

        $item = is_string($name) && class_exists($name) ? new ObjectType($name) : new MixedType;

        return new KeyedArrayType([
            new ArrayItemType_('items', new ArrayType($item)),
            new ArrayItemType_('pagination', new ObjectType(PaginationMeta::class)),
        ]);
    }
}
