<?php

namespace App\Modules\Admin\Products\Services;

use App\Core\Filters\ProductsFilter;
use App\Core\Traits\HasSorting;
use App\Modules\Admin\Products\Models\Category;
use App\Modules\Admin\Products\Models\Product;
use App\Modules\Admin\Products\Requests\ListProductsRequest;
use App\Modules\Admin\Products\Requests\ProductsRequest;
use App\Modules\Admin\Warehouses\Models\ProductStock;
use App\Modules\Admin\Warehouses\Services\WarehouseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductsService
{
    use HasSorting;

    public function __construct(private readonly WarehouseService $warehouseService) {}

    public function getProducts(ListProductsRequest $request, ProductsFilter $filter): LengthAwarePaginator
    {
        $pageSize = $request->get('pageSize');
        $sort = $request->get('sort');

        $result = Product::filter($filter);
        $result = $this->applySorting($result, $sort);

        return $result->paginate($pageSize);
    }

    public function save(ProductsRequest $request, Product $product): Product
    {
        return DB::transaction(function () use ($request, $product) {
            $category = Category::query()->findOrFail($request->integer('category_id'));
            abort_unless($request->user()->hasSubdivisionAccess($category->subdivision_id), 403);

            $oldSubdivisionId = $product->exists
                ? $product->categories()->value('subdivision_id')
                : null;
            if ($oldSubdivisionId !== null && (int) $oldSubdivisionId !== (int) $category->subdivision_id) {
                throw ValidationException::withMessages([
                    'category_id' => 'Changing the subdivision of a product is not supported.',
                ]);
            }

            $product->fill($request->only($product->getFillable()));
            $product->save();
            $product->categories()->sync([$category->id]);

            $stock = ProductStock::query()->firstOrCreate(
                ['product_id' => $product->id, 'subdivision_id' => $category->subdivision_id],
                ['quantity' => $request->input('quantity')]
            );

            $delta = (float) $request->input('quantity') - (float) $stock->quantity;
            if ($stock->wasRecentlyCreated === false && $delta != 0.0) {
                $this->warehouseService->adjust(
                    $stock,
                    number_format($delta, 3, '.', ''),
                    $request->user()->id,
                    'Product quantity changed',
                );
            }

            return $product;
        });
    }

    public function getProduct(Product $product): array
    {
        $result = $product->toArray();
        $result['description'] = $product->description()->first()?->description;
        $result['category_id'] = $product->categories()->first()?->id;

        return $result;
    }
}
