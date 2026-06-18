<?php

namespace App\Modules\Admin\Role\Services;


use App\Modules\Admin\Role\Models\Role;
use App\Modules\Admin\Role\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;

class PermService
{
    public function getPermissions(): Collection | null
    {
        return Permission::all();
    }
    
    public function save(Request $request){

        $data = $request->except('_token');
        $roles = Role::all();
        foreach ($roles as $role){
            if (isset($data[$role->id])){
                $role->savePermissions($data[$role->id]);
            } else {
                $role->savePermissions([]);
            }

        }
        return true;
    }
}
