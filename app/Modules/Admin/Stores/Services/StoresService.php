<?php

namespace App\Modules\Admin\Stores\Services;

use App\Core\Filters\StoresFilter;
use App\Core\Traits\HasSorting;
use App\Modules\Admin\Stores\Models\Store;
use App\Modules\Admin\Stores\Requests\ListStoresRequest;
use App\Modules\Admin\Stores\Requests\StoresRequest;
use Illuminate\Pagination\LengthAwarePaginator;

class StoresService
{
    use HasSorting;

    public function getStores(ListStoresRequest $request, StoresFilter $filter): LengthAwarePaginator
    {
        $query = Store::filter($filter);
        $this->applySorting($query, $request->input('sort') ?: 'title');
        $query->orderBy('id');

        return $query->paginate($request->integer('page_size', $request->integer('per_page', 20)));
    }

    public function save(StoresRequest $request, Store $store): Store
    {
        $store->fill($request->only($store->getFillable()));
        $store->save();

        return $store;
    }
}
