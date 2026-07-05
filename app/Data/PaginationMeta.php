<?php

namespace App\Data;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class PaginationMeta extends Data
{
    public function __construct(
        public int $total,
        public int $count,
        public int $perPage,
        public int $currentPage,
        public int $totalPages,
    ) {}

    public static function fromPaginator(LengthAwarePaginator $paginator): self
    {
        return new self(
            total: $paginator->total(),
            count: count($paginator->items()),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            totalPages: $paginator->lastPage(),
        );
    }

    /**
     * Wrap a paginator into the camelCase wire envelope.
     *
     * @param  class-string<Data>  $dataClass
     * @return array{items: DataCollection, pagination: self}
     */
    public static function paginate(LengthAwarePaginator $paginator, string $dataClass): array
    {
        return [
            'items' => $dataClass::collect($paginator->items(), DataCollection::class),
            'pagination' => self::fromPaginator($paginator),
        ];
    }
}
