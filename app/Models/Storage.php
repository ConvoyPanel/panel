<?php

namespace Convoy\Models;

use Convoy\Casts\StorageSizeCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Storage extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'size' => StorageSizeCast::class,
    ];

    public static array $validationRules = [
        'nickname' => 'required_if:is_shareable,1|string|max:40',
        'description' => 'nullable|string|max:191',
        'name' => 'required|string|max:191',
        'size' => 'required|numeric|min:1',
        'is_shareable' => 'required|boolean',
        'has_kvm' => 'required|boolean',
        'has_lxc' => 'required|boolean',
        'has_lxc_templates' => 'required|boolean',
        'has_backups' => 'required|boolean',
        'has_isos' => 'required|boolean',
        'has_snippets' => 'required|boolean',
    ];

    public function nodes(): BelongsToMany
    {
        return $this->belongsToMany(
            Node::class, 'storage_to_node', 'storage_id', 'node_id',
        );
    }
}
