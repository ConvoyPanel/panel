<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Ramsey\Uuid\Uuid;

/**
 * A reusable set of answers to the admin server-create form, so a repeated
 * build ("small NVMe box in Ashburn") is one pick rather than fifteen fields.
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property ?string $description
 * @property array $settings
 */
class ServerPreset extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Rules ensuring that the raw data stored in the database meets
     * expectations. The shape *inside* `settings` is validated by the form
     * request, which is the only place that knows what the create form offers.
     */
    public static array $validationRules = [
        'name' => 'required|string|between:1,191|unique:server_presets,name',
        'description' => 'nullable|string|between:1,191',
        'settings' => 'required|array',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            $model->uuid = Uuid::uuid4()->toString();
        });
    }
}
