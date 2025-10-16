<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model {
    use HasFactory;

    protected $fillable = [
        'name', 'price', 'old_price','image', 'hover_image', 'thumbnail_images', 'description',
        'availableSizes', 'availableColors', 'category' ,'stock','cost_price','sku'
    ];
    
    // protected $casts = [
    //     'availableSizes' => 'array',
    //     'availableColors' => 'array',
    //     'thumbnail_images' => 'array',
    //     'description' => 'array',
    // ];

    protected $casts = [
    'thumbnail_images' => 'array',
    'availableSizes' => 'array',
    'availableColors' => 'array',
    'cost_price' => 'decimal:2',
    'price' => 'decimal:2'
];


    public function ratings()
{
    return $this->hasMany(ProductRating::class);
}

    
}
