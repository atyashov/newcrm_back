<?php

namespace App\Modules\Admin\OutlayGroupNames\Models;

use App\Core\Traits\HasFilter;
use App\Modules\Admin\Subdivisions\Models\Subdivision;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutlayGroupName extends Model
{
    use HasFactory,
        HasFilter;

    protected $fillable = [
        'name',
        'subdivision_id',
    ];

    public function subdivision(): BelongsTo
    {
        return $this->belongsTo(Subdivision::class);
    }
}
