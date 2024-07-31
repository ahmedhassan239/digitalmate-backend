<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserApiCall extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'call_count','last_call_at'];
    protected $casts = [
        'last_call_at' => 'datetime',
    ];
}
