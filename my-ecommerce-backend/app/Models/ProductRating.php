<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;

// class ProductRating extends Model
// {
//     use HasFactory;

//     protected $fillable = [
//         'product_id',
//         'user_id',
//         'rating',
//         'review',
//     ];


//     public function product()
//     {
//         return $this->belongsTo(Product::class);
//     }


//     public function user()
//     {
//         return $this->belongsTo(User::class);
//     }
// }

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
        'review_image', // ✅ new field added
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

    // ✅ Accessor for full image URL (optional but useful)
    public function getReviewImageUrlAttribute()
    {
        return $this->review_image 
            ? asset('storage/' . $this->review_image)
            : null;
    }
}
