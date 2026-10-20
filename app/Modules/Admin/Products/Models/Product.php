<?php

namespace App\Modules\Admin\Products\Models;

use App\Core\Traits\HasFilter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Admin\Stores\Models\Store;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'site_id',
        'store_id',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->BelongsToMany(
            Category::class,
            'category_product',
            'product_id',
            'category_id'
        );
    }

    public function description(): HasOne
    {
        return $this->hasOne(
            ProductDescription::class,
            'product_id',
            'id'
        );
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(\App\Modules\Admin\Warehouses\Models\ProductStock::class);
    }
}
