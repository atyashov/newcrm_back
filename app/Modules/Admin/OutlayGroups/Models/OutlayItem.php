<?php

namespace App\Modules\Admin\OutlayGroups\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OutlayItem extends Model
{
    protected $guarded = ['id', 'outlay_group_id'];

    protected $casts = ['price' => 'decimal:2', 'quantity' => 'decimal:3', 'total_cost' => 'decimal:2', 'is_rent' => 'boolean'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(OutlayGroup::class, 'outlay_group_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Admin\Products\Models\Product::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(OutlayItemImage::class)->orderBy('position')->orderBy('id');
    }

    public function stages(): HasOne
    {
        return $this->hasOne(OutlayItemStage::class)->withDefault();
    }

    public function comments(): HasOne
    {
        return $this->hasOne(OutlayItemComment::class)->withDefault();
    }
}
