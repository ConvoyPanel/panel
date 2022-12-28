<?php

namespace Convoy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ISO extends Model
{
    use HasFactory;

    protected $table = 'iso_library';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public static $validationRules = [
        'uuid' => 'required|uuid',
        'node_id' => 'required|integer|exists:nodes,id',
        'is_successful' => 'sometimes|boolean',
        'name' => 'required|string|min:1|max:40',
        'file_name' => 'required|unique:iso_library,file_name|string|ends_with:.iso',
        'size' => 'sometimes|numeric|min:0',
        'hidden' => 'sometimes|boolean',
        'completed_at' => 'nullable|date',
    ];

    public function node()
    {
        return $this->belongsTo(Node::class);
    }
}
