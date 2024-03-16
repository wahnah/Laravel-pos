<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Phystockcount extends Model
{
    protected $table = 'phystockcount';

    protected $fillable = [
        'product_id',
        'pre_close_qty',
        'date',
    ];
}
