<?php

namespace App\Modules\Admin\Products\Models;

use App\Core\Traits\HasFilter;
use App\Modules\Admin\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory,
        HasFilter;

    protected $fillable = [
        'title',
        'alias',
        'price',
        'quantity',
        'unlimited',
        'is_rent',
        'is_service',
        'image',
        'status',
    ];

    public function categories() : BelongsToMany
    {
        return $this->BelongsToMany(
            Category::class,
            'category_product',
            'product_id',
            'category_id'
        );
    }

    public function description() : HasOne
    {
        return $this->hasOne(
            ProductDescription::class,
            'product_id',
            'id'
        );
    }

}
