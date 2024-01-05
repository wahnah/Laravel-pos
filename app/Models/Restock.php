<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restock extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'quantity_added', 'restock_date'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
