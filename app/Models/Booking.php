<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;
    protected $fillable = [
        'date',
        'client_time',
        'company_time',
        'name',
        'email',
        'phone',
        'country',
        'website_url',
        'message',
        'status',
        'source',
    ];
}
