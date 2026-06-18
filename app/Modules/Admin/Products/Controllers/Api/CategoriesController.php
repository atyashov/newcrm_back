<?php

namespace App\Modules\Admin\Products\Controllers\Api;

use App\Core\Filters\CategoriesFilter;
use App\Http\Controllers\Controller;
use App\Modules\Admin\Products\Models\Category;
use App\Modules\Admin\Products\Models\Product;
use App\Modules\Admin\Products\Requests\CategoriesRequest;
use App\Modules\Admin\Products\Requests\DestroyCategoryRequest;
use App\Modules\Admin\Products\Requests\ListCategoriesRequest;
use App\Modules\Admin\Products\Services\CategoriesService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    public function __construct(private readonly CategoriesService $service)
    {
    }

    public function index(ListCategoriesRequest $request, CategoriesFilter $filter): JsonResponse
    {
        return ResponseService::sendJsonResponse(true, 200, [], [
            'items' => $this->service->getCategories($request, $filter)
        ]);
    }

    public function show(ListCategoriesRequest $request, Category $category): JsonResponse
    {
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $category
        ]);
    }

    public function store(CategoriesRequest $request): JsonResponse
    {
        $category = $this->service->save($request, new Category());

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $category->toArray()
        ]);
    }

    public function update(CategoriesRequest $request, Category $category): JsonResponse
    {
        $category = $this->service->save($request, $category);
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $category->toArray()
        ]);
    }

    public function destroy(ListCategoriesRequest $request, Category $category): JsonResponse
    {
        $category->delete();
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $category->toArray()
        ]);
    }
}
