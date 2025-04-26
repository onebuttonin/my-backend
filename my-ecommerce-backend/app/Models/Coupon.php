<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_value',
        'expires_at',
        'usage_limit',
        'used_count',
        'is_active'
    ];

    protected $casts = [
        'expires_at' => 'date',
    ];
}
