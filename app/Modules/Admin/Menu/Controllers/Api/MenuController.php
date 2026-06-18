<?php

namespace App\Modules\Admin\Menu\Controllers\Api;

use App\Modules\Admin\Menu\Models\Menu;
use App\Modules\Admin\Menu\Models\PermissionMenu;
use App\Modules\Admin\Menu\Requests\MenuRequest;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Modules\Admin\User\Models\User;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $this->authorize('view', Menu::class);
        return ResponseService::sendJsonResponse(true, 200, [], [
           'items' => (Menu::adminMenu(Auth::user())->get())->toArray()
        ]);
    }

    public function manage(): JsonResponse
    {
        $this->authorize('manage', Menu::class);
        return ResponseService::sendJsonResponse(true, 200, [], [
            'items' => Menu::query()->with('perms')->get()->toArray()
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return JsonResponse
     */
    public function update(MenuRequest $request): JsonResponse
    {
        $this->authorize('manage', Menu::class);
        $attributes = $request->validated();
        $permissions = $attributes['permissions'] ?? [];
        $menu = $attributes['menu'] ?? [];

        PermissionMenu::query()
            ->where(['menu_id' => $menu])->delete();

        foreach ($permissions as $permission) {
            PermissionMenu::create(['menu_id' => $menu, 'permission_id' => $permission]);
        }

        $result = PermissionMenu::query()->where(['menu_id' => $menu])->get()->toArray();

        return ResponseService::sendJsonResponse(true, 200, [], [
            'items' => $result
        ]);
    }
}
