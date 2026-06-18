<?php

namespace App\Modules\Admin\Role\Services;

use App\Core\Filters\Filter;
use App\Core\Traits\HasSorting;
use App\Modules\Admin\Role\Models\Role;
use App\Modules\Admin\Role\Requests\RoleRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class RoleService
{
    use HasSorting;

    public function getRoles($pageSize, Filter $filter, $sort = null): LengthAwarePaginator
    {
        $result = Role::filter($filter);
        $result = $this->applySorting($result, $sort);

        return $result->paginate($pageSize);
    }

    public function save(RoleRequest $request, Model $model){
        
        $model->fill($request->only($model->getFillable()));
        $model->savePermissions($request->input('perms'));
        $model->save();

        return $model;
    }
}
