<?php

namespace App\Modules\Admin\Products\Controllers\Api;

use App\Core\Filters\ProductsFilter;
use App\Modules\Admin\Products\Models\Product;
use App\Modules\Admin\Products\Models\ProductDescription;
use App\Modules\Admin\Products\Requests\ListProductsRequest;
use App\Modules\Admin\Products\Requests\ProductsRequest;
use App\Modules\Admin\Products\Services\ProductsService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProductsController extends Controller
{

    private $service;

    public function __construct(ProductsService $productsService)
    {
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
            'items' => $this->service->getProducts($request, $filter)
        ]);

    }

    /**
     * Create of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }

    public function store(ProductsRequest $request): \Illuminate\Http\JsonResponse
    {

        $product = $this->service->save($request, new Product());
        $product->description()->create([
            'product_id' => $product->id,
            'description' => $request->get('description')
        ]);

        return ResponseService::sendJsonResponse(true, 200, [
            'item' => $this->service->getProduct($product)
        ]);
    }


    public function show(Product $product): JsonResponse
    {
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $this->service->getProduct($product)
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\Admin\Products\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        //
    }

    public function update(ProductsRequest $request, Product $product): JsonResponse
    {
        $product->save();
        $product
            ->description()
            ->updateOrCreate(
                ['product_id' => $product->id],
                ['description' => $request->description ?? null]
            );

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $this->service->getProduct($product)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\Admin\Products\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
