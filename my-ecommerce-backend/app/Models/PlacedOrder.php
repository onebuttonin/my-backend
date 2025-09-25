<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlacedOrder extends Model
{
    
    use HasFactory;
    protected $table = 'placedorders';  // ✅ Specify the correct table name

    
    // protected $fillable = [
    //     'name',
    //     'street1',
    //     'street2',
    //     'city',
    //     'state',
    //     'pincode',
    //     'mobile',
    //     'payment_method',
    //     'cart_id',
    //     'user_id',
    //     'cart_total',
    //     'order_status'
    // ];

    protected $fillable = [
  'user_id','name','street1','street2','city','state','pincode','mobile',
  'payment_method','order_status','cart_id','cart_total',
  'delivery_charges','packaging_cost','payment_gateway_fee','is_prepaid',
  'items_cost_sum','total_expense','net_profit','items_snapshot'
];

    // public function user()
    // {
    //     return $this->belongsTo(User::class);
    // }
    // // app/Models/PlacedOrder.php

public function user()
{
    return $this->belongsTo(\App\Models\User::class, 'user_id', 'id');
}

}
