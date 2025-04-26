<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'items',
        'total_price',
        'status',
        'payment_method',
        'payment_status',
        'shipping_address',
        'billing_address',
    ];

    protected $casts = [
        'items' => 'array', // Convert JSON to array
    ];
}
