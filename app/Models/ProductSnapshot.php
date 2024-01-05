<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSnapshot extends Model
{
    use HasFactory;

    protected $table = 'product_snapshots';

    protected $fillable = [
        'date',
        'product_id',
        'quantity',
    ];
}
