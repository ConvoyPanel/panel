<?php

namespace App\Data\Server\Backup;

use App\Enums\Server\Backup\BackupErrorCode;
use App\Models\Backup;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
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
        public ?BackupErrorCode $errorCode,
        public ?string $errorMessage,
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
            // The code is a safe, friendly enum shown to the backup owner; the
            // raw Proxmox message can leak node internals, so it is admin-only.
            errorCode: $backup->error_code,
            errorMessage: Auth::user()?->root_admin ? $backup->error_message : null,
            fileName: $backup->file_name,
            size: $backup->getRawOriginal('size') !== null ? (int) $backup->size : null,
            completedAt: $backup->completed_at
                ? CarbonImmutable::parse($backup->completed_at)
                : null,
            createdAt: CarbonImmutable::parse($backup->created_at),
        );
    }
}
