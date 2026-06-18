<?php

namespace App\Modules\Admin\Subdivisions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserSubdivision extends Model
{
    protected $table = 'users_subdivisions';

    protected $fillable = [
        'user_id',
        'subdivision_id',
    ];
}
