<?php

namespace App\Modules\Admin\Products\Models;

use Illuminate\Database\Eloquent\Model;

class ProductDescription extends Model
{
    protected $table = 'product_description';

    protected $fillable = [
        'product_id',
        'description',
    ];
}
