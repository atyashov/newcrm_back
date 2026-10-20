<?php

namespace App\Modules\Admin\Stores\Controllers\Api;

use App\Core\Filters\StoresFilter;
use App\Http\Controllers\Controller;
use App\Modules\Admin\Stores\Models\Store;
use App\Modules\Admin\Stores\Requests\ListStoresRequest;
use App\Modules\Admin\Stores\Requests\StoresRequest;
use App\Modules\Admin\Stores\Services\StoresService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;

class StoresController extends Controller
{
    public function __construct(private readonly StoresService $service) {}

    public function index(ListStoresRequest $request, StoresFilter $filter): JsonResponse
    {
        return ResponseService::sendJsonResponse(true, 200, [], [
            'items' => $this->service->getStores($request, $filter),
        ]);
    }

    public function store(StoresRequest $request): JsonResponse
    {
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $this->service->save($request, new Store()),
        ]);
    }

    public function show(Store $store): JsonResponse
    {
        return ResponseService::sendJsonResponse(true, 200, [], ['item' => $store]);
    }

    public function update(StoresRequest $request, Store $store): JsonResponse
    {
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $this->service->save($request, $store),
        ]);
    }

    public function destroy(Store $store): JsonResponse
    {
        $store->delete();

        return ResponseService::sendJsonResponse(true, 200, [], ['item' => $store]);
    }
}
