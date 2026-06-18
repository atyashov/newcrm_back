<?php

namespace App\Modules\Admin\Clients\Services;

use App\Core\Filters\Filter;
use App\Core\Traits\HasSorting;
use App\Modules\Admin\Clients\Models\LegalEntity;
use App\Modules\Admin\Clients\Requests\LegalEntityRequest;
use Illuminate\Pagination\LengthAwarePaginator;

class LegalEntityService
{
    use HasSorting;

    public function getLegalEntities($pageSize, Filter $filter, $sort = null) : LengthAwarePaginator
    {
        $result = LegalEntity::filter($filter);
        $result = $this->applySorting($result, $sort);

        return $result->paginate($pageSize);
    }

    public function save(LegalEntityRequest $request, LegalEntity $legalEntity) : LegalEntity
    {
        $legalEntity->fill($request->only($legalEntity->getFillable()));
        $legalEntity->save();
        return $legalEntity;
    }
}
