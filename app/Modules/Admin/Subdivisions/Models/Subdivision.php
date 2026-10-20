<?php

namespace App\Modules\Admin\Subdivisions\Models;

use App\Core\Traits\HasFilter;
use App\Modules\Admin\Products\Models\Category;
use App\Modules\Admin\User\Models\User;
use App\Services\PublicImageUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subdivision extends Model
{
    use HasFactory, HasFilter;

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute(): ?string
    {
        return PublicImageUrl::resolve($this->logo);
    }

    protected $fillable = [
        'title',
        'description',
        'logo',
        'amo_ids',
        'default_product_group_id_for_amo',
        'sms_sender_name',
        'site_link',
    ];

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'users_subdivisions',
            'subdivision_id',
            'user_id'
        );
    }
}
