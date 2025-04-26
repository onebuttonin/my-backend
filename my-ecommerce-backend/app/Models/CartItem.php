<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $table = 'cart_items';  
    protected $fillable = ['cart_id', 'product_id', 'size', 'quantity'];

    // Relationship with Cartss
    public function cartss()
    {
        return $this->belongsTo(Cartss::class, 'cart_id');
    }

    // Relationship with Product
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
