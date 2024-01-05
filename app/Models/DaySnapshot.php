<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DaySnapshot extends Model
{
    use HasFactory;

    protected $table = 'day_snapshots';

    protected $fillable = [
        'date',
        'product_id',
        'restock_quantity',
        'order_quantity',
        'order_total',
    ];
}
