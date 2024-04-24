<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = "products";
    protected $fillable = [
        'name',
        'description',
        'slug',
        'price',
        'active'
    ];

    function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id')->orderBy('updated_at', 'desc');
    }
    function discount()
    {
        return $this->hasOne(ProductDiscount::class, 'product_id')->orderBy('updated_at', 'desc');
    }
}