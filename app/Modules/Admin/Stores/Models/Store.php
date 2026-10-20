<?php

namespace App\Modules\Admin\Stores\Models;

use Illuminate\Database\Eloquent\Model;
use App\Core\Traits\HasFilter;
use App\Modules\Admin\Products\Models\Product;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFilter;

    protected $fillable = ['title'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
