<?php

namespace App\Modules\Admin\OutlayGroups\Models;

use App\Modules\Admin\OutlayGroupNames\Models\OutlayGroupName;
use App\Modules\Admin\Outlays\Models\Outlay;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutlayGroup extends Model
{
    protected $fillable = ['outlay_group_name_id', 'position'];

    public function outlay(): BelongsTo
    {
        return $this->belongsTo(Outlay::class);
    }

    public function groupName(): BelongsTo
    {
        return $this->belongsTo(OutlayGroupName::class, 'outlay_group_name_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OutlayItem::class)->orderBy('position')->orderBy('id');
    }
}
