<?php

namespace App\Modules\Admin\Role\Controllers\Api;

use App\Core\Filters\RoleFilter;
use App\Modules\Admin\Role\Models\Permission;
use App\Modules\Admin\Role\Services\RoleService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Admin\Role\Services\PermService;

class PermissionController extends Controller
{

    public function __construct(
        private PermService $service
    )
    {
    }

    /**
     * Display a listing of the resource.
     *
     */
    public function index(Request $request, PermService $service): JsonResponse
    {
        $this->authorize('view', Permission::class);
        return ResponseService::SendJsonResponse(true, 200, [], [
            'items' => $this->service->getPermissions()
        ]);
    }

    /**
     * Create of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\Admin\Role\Models\Role  $role
     */
    public function show(Role $role): JsonResponse
    {
        return ResponseService::SendJsonResponse(true, 200, [], [
            'item' => $role->with('perms')->get()
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\Admin\Role\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function edit(Role $role)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\Admin\Role\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Role $role)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\Admin\Role\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role)
    {
        //
    }
}
