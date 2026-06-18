<?php

namespace App\Modules\Admin\ClientTypes\Models;

use App\Core\Traits\HasFilter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientType extends Model
{
    use HasFactory,
        HasFilter;

    protected $fillable = [
        'title'
    ];
}
