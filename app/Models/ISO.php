<?php

namespace Convoy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ISO extends Model
{
    use HasFactory;

    protected $table = 'iso_library';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'is_successful' => 'boolean',
        // Bytes, not the whole mebibytes servers and backups are stored in:
        // that rounded every ISO down, so a 2.95 MiB file read as "2 MiB" in
        // the library and 2.95 MiB on the server's drive.
        'size' => 'integer',
        'hidden' => 'boolean',
    ];

    public static array $validationRules = [
        'node_id' => 'required|integer|exists:nodes,id',
        'is_successful' => 'sometimes|boolean',
        'name' => 'required|string|min:1|max:40',
        'file_name' => 'required|string|ends_with:.iso|max:191',
        'size' => 'sometimes|numeric|min:0',
        'hidden' => 'sometimes|boolean',
        'completed_at' => 'nullable|date',
    ];

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ISO $user) {
            $user->uuid = Str::uuid()->toString();
        });
    }
}
