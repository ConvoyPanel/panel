<?php

namespace Convoy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SSOToken extends Model
{
    use HasFactory;

    protected $table = 'sso_tokens';

    protected $fillable = [
        'user_id', 'token', 'used',
    ];
}
