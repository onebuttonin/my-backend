<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $table = 'carts';  // Specify the table name
    protected $fillable = ['user_id', 'session_id', 'status'];

    // Relationship with CartItem
    public function cartItems()
    {
        return $this->hasMany(CartItem::class, 'cart_id');
    }

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
