<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

/**
 * A disk image that is still arriving.
 *
 * Multi-gigabyte files do not fit in one request -- Cloudflare rejects a body
 * over 100 MB on most plans, PHP has its own ceilings, and a connection that
 * drops at 90% would otherwise cost the whole transfer. So an upload is a
 * sequence of chunks appended to one file, and this row is the bookkeeping:
 * how much has landed, what the whole thing is supposed to weigh, and who is
 * allowed to carry on sending it.
 *
 * Deliberately short-lived. The row exists only between the first chunk and the
 * assembled file; once the bytes are hashed and stored under their own digest,
 * the upload has no further identity and is deleted.
 *
 * @property int $id
 * @property string $uuid
 * @property ?int $user_id
 * @property string $file_name
 * @property string $format
 * @property int $expected_size
 * @property ?string $expected_sha256
 * @property int $received_bytes
 */
class ImageUpload extends Model
{
    public static array $validationRules = [
        'file_name' => 'required|string|max:255',
        'format' => 'required|string|in:qcow2,raw',
        'expected_size' => 'required|integer|min:1',
        'expected_sha256' => 'nullable|string|regex:/^[a-f0-9]{64}$/i',
        'received_bytes' => 'sometimes|integer|min:0',
    ];

    protected $guarded = ['id'];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Where the partial file lives on the panel's own local disk.
     *
     * Local rather than the artifacts disk even when that one is remote: chunks
     * are appended, and an object store has no append. The finished file is
     * streamed across once, which is the only point at which the artifacts disk
     * needs to be involved.
     */
    public function partialPath(): string
    {
        return 'image-uploads/'.$this->uuid.'.part';
    }

    public function isComplete(): bool
    {
        return $this->received_bytes >= $this->expected_size;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ImageUpload $model) {
            $model->uuid = Uuid::uuid4()->toString();
        });
    }
}
