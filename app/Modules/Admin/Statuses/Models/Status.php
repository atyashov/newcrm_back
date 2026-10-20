<?php

namespace App\Modules\Admin\Statuses\Models;

use App\Modules\Admin\Outlays\Models\Outlay;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'sort_order',
        'subdivision_id',
        'affects_stock',
    ];

    protected $casts = [
        'affects_stock' => 'boolean',
    ];

    public function outlays()
    {
        return $this->hasMany(Outlay::class);
    }

    public function getStatuses()
    {
        return $this->orderBy('sort_order', 'DESC')->get();
    }
}
