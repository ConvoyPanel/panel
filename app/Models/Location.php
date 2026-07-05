<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property string $short_code
 * @property ?string $description
 * @property int $nodes_count
 * @property int $servers_count
 */
class Location extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * Fields that are not mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Rules ensuring that the raw data stored in the database meets expectations.
     */
    public static array $validationRules = [
        'short_code' => 'required|string|between:1,60|unique:locations,short_code',
        'description' => 'string|nullable|between:1,191',
    ];

    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    /**
     * Gets the nodes in a specified location.
     */
    public function nodes(): HasMany
    {
        return $this->hasMany(Node::class);
    }

    /**
     * Gets the servers within a given location.
     */
    public function servers(): HasManyThrough
    {
        return $this->hasManyThrough(Server::class, Node::class);
    }
}
