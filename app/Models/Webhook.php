<?php

namespace App\Models;

use App\Support\Webhooks\WebhookEvents;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * An address the panel POSTs events to.
 *
 * @property int $id
 * @property string $uuid
 * @property ?string $description
 * @property string $url
 * @property string $secret
 * @property list<string> $events
 * @property bool $enabled
 * @property ?int $created_by
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
class Webhook extends Model
{
    /** Where the enabled webhooks are cached; every audit entry asks for them. */
    public const CACHE_KEY = 'webhooks.enabled';

    protected $fillable = [
        'description',
        'url',
        'secret',
        'events',
        'enabled',
        'created_by',
    ];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'events' => 'array',
            'enabled' => 'boolean',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $webhook) {
            $webhook->uuid ??= (string) Str::uuid();
        });

        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function subscribesTo(string $event): bool
    {
        return WebhookEvents::matches($this->events ?? [], $event);
    }

    /** A fresh signing secret, prefixed so one found in a log is recognisable. */
    public static function generateSecret(): string
    {
        return 'whsec_'.Str::random(40);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<WebhookDelivery, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /** @return HasOne<WebhookDelivery, $this> */
    public function latestDelivery(): HasOne
    {
        return $this->hasOne(WebhookDelivery::class)->latestOfMany();
    }
}
