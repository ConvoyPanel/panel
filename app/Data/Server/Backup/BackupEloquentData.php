<?php

namespace App\Data\Server\Backup;

use App\Models\Backup;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class BackupEloquentData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $serverId,
        public int $storageId,
        public string $name,
        public ?string $description,
        public bool $isLocked,
        public ?string $errors,
        public ?string $fileName,
        public ?int $size,
        public ?CarbonImmutable $completedAt,
        public CarbonImmutable $createdAt,
    ) {}

    public static function fromModel(Backup $backup): self
    {
        return new self(
            id: $backup->id,
            uuid: $backup->uuid,
            serverId: $backup->server_id,
            storageId: $backup->storage_id,
            name: $backup->name,
            description: $backup->description,
            isLocked: (bool) $backup->is_locked,
            errors: $backup->errors,
            fileName: $backup->file_name,
            size: $backup->size !== null ? (int) $backup->size : null,
            completedAt: $backup->completed_at
                ? CarbonImmutable::parse($backup->completed_at)
                : null,
            createdAt: CarbonImmutable::parse($backup->created_at),
        );
    }
}
