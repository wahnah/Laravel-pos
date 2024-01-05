<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image',
        'barcode',
        'price',
        'quantity',
        'status',
        'category_id',
        'orderprice',
    ];


    public function restocks()
{
    return $this->hasMany(Restock::class);
}
}
