<?php

namespace App\Modules\Admin\Warehouses\Models;

use App\Modules\Admin\Products\Models\Product;
use App\Modules\Admin\Subdivisions\Models\Subdivision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductStock extends Model
{
    protected $fillable = ['product_id', 'subdivision_id', 'quantity'];

    protected $casts = ['quantity' => 'decimal:3'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function subdivision(): BelongsTo
    {
        return $this->belongsTo(Subdivision::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
