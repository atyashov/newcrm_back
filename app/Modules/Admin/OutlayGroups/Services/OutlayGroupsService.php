<?php

namespace App\Modules\Admin\OutlayGroups\Services;

use App\Modules\Admin\OutlayGroups\Models\OutlayGroup;
use App\Modules\Admin\OutlayGroups\Requests\ListOutlayGroupsRequest;
use Illuminate\Database\Eloquent\Collection;

class OutlayGroupsService
{
    public function getOutlayGroups(ListOutlayGroupsRequest $request) : Collection
    {
        return OutlayGroup::query()
            ->whereHas('outlay', fn ($query) => $query->where('subdivision_id', (int) $request->input('subdivision_id')))
            ->where('outlay_id', $request->input('outlay_id'))
            ->with(['groupName', 'items.images'])
            ->orderBy('position')->orderBy('id')->get();
    }
}
