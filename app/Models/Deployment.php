<?php

namespace Convoy\Models;


use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deployment extends Model
{
    const UPDATED_AT = null;

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'created_at',
    ];

    /**
     * Rules ensuring that the raw data stored in the database meets expectations.
     */
    public static array $validationRules = [
        'server_id' => 'required|exists:servers,id',
        'template_id' => 'nullable|exists:templates,id',
        'should_create_vm' => 'required|boolean',
        'start_on_completion' => 'required|boolean',
        'build_successful' => 'required|boolean',
        'build_progress' => 'required|integer|min:0|max:100',
        'built_vm_at' => 'nullable|date',
        'sync_successful' => 'required|boolean',
        'synced_vm_at' => 'nullable|date',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
