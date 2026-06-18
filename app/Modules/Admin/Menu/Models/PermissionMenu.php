<?php

namespace App\Modules\Admin\Menu\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionMenu extends Model
{
    protected $table = 'permission_menu';

    protected $fillable = [
        'permission_id',
        'menu_id',
    ];
}
