<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlacedOrder extends Model
{
    
    use HasFactory;
    protected $table = 'placedorders';  // ✅ Specify the correct table name

    
    protected $fillable = [
        'name',
        'street1',
        'street2',
        'city',
        'state',
        'pincode',
        'mobile',
        'payment_method',
        'cart_id',
        'user_id',
        'cart_total',
        'order_status'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
