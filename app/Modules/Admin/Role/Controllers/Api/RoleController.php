<?php

namespace App\Modules\Admin\Role\Controllers\Api;

use App\Core\Filters\RoleFilter;
use App\Modules\Admin\Role\Models\Role;
use App\Modules\Admin\Role\Services\RoleService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Admin\Role\Requests\RoleRequest;
class RoleController extends Controller
{

    public function __construct(
        private RoleService $service
    )
    {
    }

    /**
     * Display a listing of the resource.
     *
     */
    public function index(Request $request, RoleFilter $filter): JsonResponse
    {
        $this->authorize('view', Role::class);
        return ResponseService::SendJsonResponse(true, 200, [], [
            'items' => $this->service->getRoles($request->get('page_size') ?? 10, $filter, $request->get('sort'))
        ]);
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
     * Update the specified resource in storage.
     *
     * @param  RoleRequest  $request
     * @param  Role  $role
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(RoleRequest $request, Role $role)
    {
        $this->authorize('edit', Role::class);
        $role = $this->service->save($request, $role);
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $role->toArray()
        ]);
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
