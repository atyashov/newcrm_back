<?php

namespace App\Modules\Admin\Statuses\Models;

use App\Modules\Admin\Outlays\Models\Outlay;
use App\Modules\Admin\Statuses\Requests\StatusesRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'sort_order',
        'subdivision_id',
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
