<?php

namespace App\Models;

use App\Casts\StorageSizeCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public static array $validationRules = [
        'uuid' => 'required|uuid',
        'server_id' => 'required|exists:servers,id',
        'is_successful' => 'sometimes|boolean',
        'is_locked' => 'sometimes|boolean',
        'name' => 'required|string|min:1|max:40',
        'file_name' => 'nullable|string',
        'size' => 'nullable|numeric|min:0',
        'completed_at' => 'nullable|date',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'size' => StorageSizeCast::class,
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
