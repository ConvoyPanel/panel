<?php

namespace App\Models;

use App\Casts\StorageSizeCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Storage extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [
        'id',
    ];

    public static array $validationRules = [
        'display_name' => 'nullable|string|max:40',
        'description' => 'nullable|string|max:191',
        'name' => 'required|string|max:191',
        'size' => 'required|numeric|min:1',
        'is_shareable' => 'required|boolean',
        'stores_kvm' => 'required|boolean',
        'stores_lxc' => 'required|boolean',
        'stores_lxc_templates' => 'required|boolean',
        'stores_backups' => 'required|boolean',
        'stores_iso' => 'required|boolean',
        'stores_snippets' => 'required|boolean',
    ];

    protected function casts(): array
    {
        return [
            'size' => StorageSizeCast::class,
            'is_shareable' => 'boolean',
            'stores_kvm' => 'boolean',
            'stores_lxc' => 'boolean',
            'stores_lxc_templates' => 'boolean',
            'stores_backups' => 'boolean',
            'stores_iso' => 'boolean',
            'stores_snippets' => 'boolean',
        ];
    }

    public function nodes(): BelongsToMany
    {
        return $this->belongsToMany(
            Node::class,
            'storage_to_node',
            'storage_id',
            'node_id',
        )->withPivot('backup_order');
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
