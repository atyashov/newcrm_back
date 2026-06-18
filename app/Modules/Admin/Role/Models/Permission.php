<?php

namespace App\Modules\Admin\Role\Models;

use App\Modules\Admin\Menu\Models\Menu;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'alias',
        'title',
        'description',
    ];

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'permission_menu');
    }
}
