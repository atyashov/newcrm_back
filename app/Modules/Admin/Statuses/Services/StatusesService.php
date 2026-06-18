<?php

namespace App\Modules\Admin\Statuses\Services;

use App\Modules\Admin\Statuses\Models\Status;
use App\Modules\Admin\Statuses\Requests\StatusesRequest;

class StatusesService
{
    public function getStatuses(): \Illuminate\Database\Eloquent\Collection | null
    {
        return Status::all();
    }

    public function save(StatusesRequest|\Illuminate\Http\Request $request, Status $status) : Status
    {
        $status->fill($request->only($status->getFillable()));
        $status->save();
        return $status;
    }

}
