<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'rating',
        'review',
    ];

    // Optional: Relationship back to product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Optional: Relationship back to user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
