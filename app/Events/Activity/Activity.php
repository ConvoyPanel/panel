<?php

namespace App\Events\Activity;

use Illuminate\Support\Str;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

abstract class Activity
{
    public function __construct(public ActivityLog $model)
    {
    }

    public function is(string $event): bool
    {
        return $this->model->event === $event;
    }

    public function actor(): ?Model
    {
        return $this->isSystem() ? null : $this->model->actor;
    }

    public function isServerEvent(): bool
    {
        return Str::startsWith($this->model->event, 'server:');
    }

    public function isUserEvent(): bool
    {
        return Str::startsWith($this->model->event, 'user:');
    }

    public function isSystem()
    {
        // @phpstan-ignore-next-line
        return is_null($this->model->actor_id);
    }
}
