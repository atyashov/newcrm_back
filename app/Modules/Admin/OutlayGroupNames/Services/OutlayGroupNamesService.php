<?php

namespace App\Modules\Admin\OutlayGroupNames\Services;

use App\Core\Filters\OutlayGroupNamesFilter;
use App\Core\Traits\HasSorting;
use App\Modules\Admin\OutlayGroupNames\Models\OutlayGroupName;
use App\Modules\Admin\OutlayGroupNames\Requests\ListOutlayGroupNamesRequest;
use App\Modules\Admin\OutlayGroupNames\Requests\OutlayGroupNamesRequest;
use Illuminate\Pagination\LengthAwarePaginator;

class OutlayGroupNamesService
{
    use HasSorting;

    public function getOutlayGroupNames(
        ListOutlayGroupNamesRequest $request,
        OutlayGroupNamesFilter $filter
    ): LengthAwarePaginator {
        $result = OutlayGroupName::filter($filter)
            ->with('subdivision');

        if ($request->filled('sort')) {
            $result = $this->applySorting($result, $request->string('sort')->toString());
        } else {
            $result
                ->orderBy('name')
                ->orderBy('id');
        }

        return $result->paginate(
            $request->integer('page_size', $request->integer('per_page', 20))
        );
    }

    public function save(
        OutlayGroupNamesRequest $request,
        OutlayGroupName $outlayGroupName
    ): OutlayGroupName {
        $outlayGroupName->fill($request->only($outlayGroupName->getFillable()));
        $outlayGroupName->save();

        return $outlayGroupName;
    }
}
