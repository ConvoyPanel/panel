<?php

namespace App\Models;

use App\Enums\Webhook\WebhookDeliveryStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One event sent, or being sent, to one webhook, with what the receiver answered last.
 *
 * @property int $id
 * @property string $uuid
 * @property int $webhook_id
 * @property string $event
 * @property array<string, mixed> $payload
 * @property WebhookDeliveryStatus $status
 * @property int $attempts
 * @property ?int $response_status
 * @property ?string $response_body
 * @property ?string $error
 * @property ?CarbonImmutable $delivered_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property Webhook $webhook
 */
class WebhookDelivery extends Model
{
    use Prunable;

    /** How long a delivery is kept. Long enough to debug a receiver, short enough to stay small. */
    public const RETENTION_DAYS = 30;

    protected $fillable = [
        'webhook_id',
        'event',
        'payload',
        'status',
        'attempts',
        'response_status',
        'response_body',
        'error',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => WebhookDeliveryStatus::class,
            'attempts' => 'integer',
            'response_status' => 'integer',
            'delivered_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $delivery) {
            $delivery->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(self::RETENTION_DAYS));
    }
}
