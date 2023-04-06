<?php

namespace Convoy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Location extends Model
{
    use HasFactory;

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Rules ensuring that the raw data stored in the database meets expectations.
     *
     * @var array
     */
    public static $validationRules = [
        'short_code' => 'required|string|between:1,60|unique:locations,short_code',
        'description' => 'string|nullable|between:1,191',
    ];

    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    /**
     * Gets the nodes in a specified location.
     *
     * @return HasMany
     */
    public function nodes()
    {
        return $this->hasMany(Node::class);
    }

    /**
     * Gets the servers within a given location.
     *
     * @return HasManyThrough
     */
    public function servers()
    {
        return $this->hasManyThrough(Server::class, Node::class);
    }
}
