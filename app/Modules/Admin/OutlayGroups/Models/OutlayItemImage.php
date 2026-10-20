<?php

namespace App\Modules\Admin\OutlayGroups\Models;

use App\Services\PublicImageUrl;
use Illuminate\Database\Eloquent\Model;

class OutlayItemImage extends Model
{
    protected $fillable = ['path', 'position'];

    protected $appends = ['url'];

    public function getUrlAttribute(): ?string
    {
        return PublicImageUrl::resolve($this->path);
    }
}
