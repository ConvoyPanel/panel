<?php

namespace App\Data\Location;

use App\Models\Location;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class LocationData extends Data
{
    public function __construct(
        public int $id,
        public string $shortCode,
        public ?string $description,
        public int $nodesCount,
        public int $serversCount,
    ) {}

    public static function fromModel(Location $location): self
    {
        return new self(
            id: $location->id,
            shortCode: $location->short_code,
            description: $location->description,
            nodesCount: (int) ($location->nodes_count ?? 0),
            serversCount: (int) ($location->servers_count ?? 0),
        );
    }
}
