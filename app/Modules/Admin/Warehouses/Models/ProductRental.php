<?php

namespace App\Modules\Admin\Warehouses\Models;

use App\Modules\Admin\OutlayGroups\Models\OutlayItem;
use App\Modules\Admin\Outlays\Models\Outlay;
use App\Modules\Admin\Products\Models\Product;
use App\Modules\Admin\Subdivisions\Models\Subdivision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRental extends Model
{
    public const ACTIVE_STATUSES = ['reserved', 'issued'];

    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'decimal:3',
        'starts_at' => 'datetime:Y-m-d H:i:s',
        'ends_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function outlay(): BelongsTo
    {
        return $this->belongsTo(Outlay::class);
    }

    public function outlayItem(): BelongsTo
    {
        return $this->belongsTo(OutlayItem::class);
    }

    public function subdivision(): BelongsTo
    {
        return $this->belongsTo(Subdivision::class);
    }
}
