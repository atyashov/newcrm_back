<?php

namespace App\Modules\Admin\Products\Controllers\Api;

use App\Core\Filters\ProductsFilter;
use App\Http\Controllers\Controller;
use App\Modules\Admin\Products\Models\Product;
use App\Modules\Admin\Products\Requests\AvailabilityRequest;
use App\Modules\Admin\Products\Requests\BatchAvailabilityRequest;
use App\Modules\Admin\Products\Requests\ListProductsRequest;
use App\Modules\Admin\Products\Requests\ProductsRequest;
use App\Modules\Admin\Products\Requests\ProduuctsSimpleRequest;
use App\Modules\Admin\Products\Services\ProductsService;
use App\Modules\Admin\Subdivisions\Models\Subdivision;
use App\Modules\Admin\Warehouses\Services\WarehouseService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class ProductsController extends Controller
{
    private $service;

    public function __construct(
        ProductsService $productsService,
        private readonly WarehouseService $warehouseService,
    ) {
        $this->service = $productsService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(ListProductsRequest $request, ProductsFilter $filter)
    {
        $this->authorize('view', Product::class);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'items' => $this->service->getProducts($request, $filter),
        ]);

    }

    public function availability(AvailabilityRequest $request): JsonResponse
    {
        $quantity = $this->warehouseService->availability(
            $request->integer('product_id'),
            $request->integer('subdivision_id'),
            $request->validated('starts_at'),
            $request->validated('ends_at'),
            $request->filled('outlay_id') ? $request->integer('outlay_id') : null,
        );

        return ResponseService::sendJsonResponse(true, 200, [], ['quantity' => $quantity]);
    }

    public function batchAvailability(BatchAvailabilityRequest $request): JsonResponse
    {
        $items = $this->warehouseService->batchAvailability(
            $request->validated('product_ids'),
            $request->integer('subdivision_id'),
            $request->validated('starts_at'),
            $request->validated('ends_at'),
            $request->filled('outlay_id') ? $request->integer('outlay_id') : null,
        );

        return ResponseService::sendJsonResponse(true, 200, [], ['items' => $items]);
    }

    public function simple(ProduuctsSimpleRequest $request)
    {
        $data = $request->validated();

        $subdivision = Subdivision::findOrFail($data['subdivision_id']);

        if (! $subdivision->site_link) {
            return ResponseService::sendJsonResponse(false, 422, [
                'subdivision_id' => ['Subdivision site link is empty'],
            ]);
        }

        $excludeIds = [];

        if ($request->boolean('only_new')) {
            $excludeIds = Product::query()
                ->whereHas('categories', function ($category) use ($data) {
                    $category->where('subdivision_id', $data['subdivision_id']);
                })
                ->whereNotNull('site_id')
                ->pluck('site_id')
                ->filter()
                ->values()
                ->all();
        }

        $url = rtrim($subdivision->site_link, '/').'/index.php?'.http_build_query([
            'route' => 'api/simple/products',
            'page' => $data['page'],
            'per_page' => $data['per_page'],
        ]);

        $excludeIdsString = implode(',', $excludeIds);

        $response = Http::asMultipart()->post($url, [
            'exclude_ids' => $excludeIdsString,
        ]);

        return response($response->body(), $response->status())
            ->header('Content-Type', $response->header('Content-Type', 'application/json'));
    }

    /**
     * Create of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {}

    public function store(ProductsRequest $request): \Illuminate\Http\JsonResponse
    {

        $product = $this->service->save($request, new Product);
        $product->description()->create([
            'product_id' => $product->id,
            'description' => $request->get('description'),
        ]);

        return ResponseService::sendJsonResponse(true, 200, [
            'item' => $this->service->getProduct($product),
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $this->service->getProduct($product),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        //
    }

    public function update(ProductsRequest $request, Product $product): JsonResponse
    {
        $product = $this->service->save($request, $product);
        $product
            ->description()
            ->updateOrCreate(
                ['product_id' => $product->id],
                ['description' => $request->description ?? null]
            );

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $this->service->getProduct($product),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
