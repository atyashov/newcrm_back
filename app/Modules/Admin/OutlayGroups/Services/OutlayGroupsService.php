<?php

namespace App\Modules\Admin\OutlayGroups\Services;

use App\Modules\Admin\OutlayGroups\Models\OutlayGroup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class OutlayGroupsService
{
    public function getOutlayGroups(Request $request) : Collection
    {
        return OutlayGroup::where('subdivision_id', '=', $request->query('subdivision_id'))->get();
    }
}
