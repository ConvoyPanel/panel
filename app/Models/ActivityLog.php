<?php

namespace Convoy\Models;

use Carbon\Carbon;
use LogicException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [
        'id',
        'timestamp',
    ];

    protected $casts = [
        'properties' => 'collection',
        'timestamp' => 'datetime',
    ];

    protected $with = ['subjects'];

    public static $validationRules = [
        'event' => ['required', 'string'],
        'batch' => ['nullable', 'uuid'],
        'ip' => ['required', 'string'],
        'description' => ['nullable', 'string'],
        'properties' => ['array'],
    ];

    public function actor(): MorphTo
    {
        $morph = $this->morphTo();
        if (method_exists($morph, 'withTrashed')) {
            return $morph->withTrashed();
        }

        return $morph;
    }

    public function subjects()
    {
        return $this->hasMany(ActivityLogSubject::class);
    }

    public function scopeForEvent(Builder $builder, string $action): Builder
    {
        return $builder->where('event', $action);
    }

    /**
     * Scopes a query to only return results where the actor is a given model.
     */
    public function scopeForActor(Builder $builder, Model $actor): Builder
    {
        return $builder->whereMorphedTo('actor', $actor);
    }

    /**
     * Returns models to be pruned.
     *
     * @see https://laravel.com/docs/9.x/eloquent#pruning-models
     */
    public function prunable()
    {
        if (is_null(config('activity.prune_days'))) {
            throw new LogicException('Cannot prune activity logs: no "prune_days" configuration value is set.');
        }

        return static::where('created_at', '<=', Carbon::now()->subDays(config('activity.prune_days')));
    }
}
