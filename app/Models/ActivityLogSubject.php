<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLogSubject extends Model
{
    public function activityLog(): BelongsTo
    {
        return $this->belongsTo(ActivityLog::class);
    }

    public function subject(): MorphTo
    {
        $morph = $this->morphTo();
        if (method_exists($morph, 'withTrashed')) {
            return $morph->withTrashed();
        }

        return $morph;
    }
}
