<?php

namespace App\Modules\Admin\Products\Models;

use App\Core\Traits\HasFilter;
use App\Modules\Admin\Subdivisions\Models\Subdivision;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory, HasFilter;

    protected $fillable = [
        'title',
        'alias',
        'subdivision_id',
        'parent',
        'status',
    ];

    public function subdivision() : BelongsTo
    {
        return $this->belongsTo(Subdivision::class);
    }

    public function products() : BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_product');
    }
}
