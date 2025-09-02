<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model {
    use HasFactory;

    protected $fillable = [
        'name', 'price', 'old_price','image', 'hover_image', 'thumbnail_images', 'description',
        'availableSizes', 'availableColors', 'category' ,'stock'
    ];
    
    protected $casts = [
        'availableSizes' => 'array',
        'availableColors' => 'array',
        'thumbnail_images' => 'array',
    ];

    public function ratings()
{
    return $this->hasMany(ProductRating::class);
}

    
}
