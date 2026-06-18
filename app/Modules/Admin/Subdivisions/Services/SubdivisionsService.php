<?php

namespace App\Modules\Admin\Subdivisions\Services;

use App\Modules\Admin\Subdivisions\Models\Subdivision;
use App\Modules\Admin\Subdivisions\Requests\SubdivisionsRequest;

class SubdivisionsService
{

    public function getSubdivisions(): \Illuminate\Database\Eloquent\Collection
    {
        return Subdivision::all();
    }

    public function save(SubdivisionsRequest|\Illuminate\Http\Request $request, Subdivision $subdivision) : Subdivision
    {
        $subdivision->fill($request->only($subdivision->getFillable()));
        $subdivision->save();
        return $subdivision;
    }
}
