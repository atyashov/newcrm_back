<?php

namespace App\Modules\Admin\Products\Services;

use App\Core\Filters\Filter;
use App\Core\Traits\HasSorting;
use App\Modules\Admin\Products\Models\Category;
use App\Modules\Admin\Products\Requests\CategoriesRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoriesService
{
    use HasSorting;
    public function getCategories(Request $request, Filter $filter): lengthAwarePaginator
    {
        $pageSize = $request->get('pageSize');
        $sort = $request->get('sort');

        $result = Category::filter($filter);
        $result = $this->applySorting($result, $sort);

        return $result->paginate($pageSize);
    }

    public function save(CategoriesRequest $request, Category $category): Category
    {
        $category->fill($request->only($category->getFillable()));
        $category->save();
        return $category;
    }
}
