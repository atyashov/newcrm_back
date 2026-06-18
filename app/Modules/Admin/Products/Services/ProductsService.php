<?php

namespace App\Modules\Admin\Products\Services;

use App\Core\Filters\Filter;
use App\Core\Filters\ProductsFilter;
use App\Core\Traits\HasSorting;
use App\Modules\Admin\Products\Models\Category;
use App\Modules\Admin\Products\Models\Product;
use App\Modules\Admin\Products\Requests\ListProductsRequest;
use App\Modules\Admin\Products\Requests\ProductsRequest;
use App\Modules\Admin\Subdivisions\Models\Subdivision;
use App\Modules\Admin\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;


class ProductsService
{
    use HasSorting;
    public function getProducts(ListProductsRequest $request, ProductsFilter $filter): LengthAwarePaginator
    {
        $pageSize = $request->get('pageSize');
        $sort = $request->get('sort');

        $result = Product::filter($filter);
        $result = $this->applySorting($result, $sort);

        return $result->paginate($pageSize);
    }

    public function save(ProductsRequest $request, Product $product) : Product
    {
        $product->fill($request->only($product->getFillable()));
        $product->save();

        $product->categories()->sync([$request->input('category_id')]);
        return $product;
    }

    public function getProduct(Product $product) : array
    {
        $result = $product->toArray();
        $result['description'] = $product->description()->first()->description;
        $result['category_id'] = $product->categories()->first()->id;

        return $result;
    }
}
