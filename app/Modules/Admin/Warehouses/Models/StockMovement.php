<?php

namespace App\Modules\Admin\Warehouses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'decimal:3',
        'occurred_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(ProductStock::class, 'product_stock_id');
    }
}
