<?php

namespace App\Models;

use App\Events\Activity\ActivityLogged;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Event;

class ActivityLog extends Model
{
    use HasFactory;

    //public $timestamps = false;

    protected $guarded = [
        'id',
        //'timestamp',
    ];

    protected $with = ['subjects'];

    public static $validationRules = [
        'event' => ['required', 'string'],
        'batch' => ['nullable', 'uuid'],
        'ip' => ['required', 'string'],
        'description' => ['nullable', 'string'],
        'properties' => ['array'],
    ];

    public static $eventTypes = [
        'server:details.update' => [
            'timeout' => 240 // seconds
        ],
        'server:install' => [
            'timeout' => false, // determined by status of UPID
        ],
        'server:uninstall' => [
            'timeout' => false,
        ],
        'server:rebuild' => [
            'timeout' => 1000
        ],
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
     * Boots the model event listeners. This will trigger an activity log event every
     * time a new model is inserted which can then be captured and worked with as needed.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function (self $model) {
            Event::dispatch(new ActivityLogged($model));
        });
    }
}
