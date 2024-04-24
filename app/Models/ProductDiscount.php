<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductDiscount extends Model
{
    use HasFactory;

    protected $table = "product_discounts";
    protected $fillable = [
        'product_id',
        'type',
        'discount'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
