<?php

namespace App\Modules\Admin\Menu\Models;

use App\Modules\Admin\Role\Models\Permission;
use App\Modules\Admin\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    const MENU_TYPE_FRONT = 'front';
    const MENU_TYPE_ADMIN = 'admin';

    public function perms(){
        return $this->belongsToMany(Permission::class, 'permission_menu');
    }

    public function scopeAdminMenu($query, User $user) {
        return $query->
            where('type', self::MENU_TYPE_ADMIN)->
            whereHas('perms', function($q) use($user){
                $arr = collect($user->getMergedPermissions())->map(function($item){
                    return $item['id'];
                });
                $q->whereIn('id', $arr->toArray())
            ->orderBy('sort_order');
        });
    }

    public function scopeMenuByType($query, $type) {
        return $query->
            where('type', $type)->
            orderBy('parent')->
            orderBy('sort_order');
    }
}
