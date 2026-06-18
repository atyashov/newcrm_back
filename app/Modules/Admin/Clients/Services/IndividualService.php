<?php

namespace App\Modules\Admin\Clients\Services;

use App\Core\Filters\Filter;
use App\Core\Traits\HasSorting;
use App\Modules\Admin\Clients\Models\Individual;
use App\Modules\Admin\Clients\Requests\IndividualRequest;
use Illuminate\Pagination\LengthAwarePaginator;

class IndividualService
{
    use HasSorting;

    /**
     * @param $pageSize
     * @param Filter $filter
     * @return LengthAwarePaginator
     */
    public function getIndividuals($pageSize, Filter $filter, $sort = null) : LengthAwarePaginator
    {
        $result = Individual::filter($filter);
        $result = $this->applySorting($result, $sort);

        return $result->paginate($pageSize);
    }

    public function save(IndividualRequest $request, Individual $individual) : Individual
    {
        $individual->fill($request->only($individual->getFillable()));
        $individual->save();
        return $individual;
    }
}
