<?php

namespace App\Modules\Admin\Subdivisions\Services;

use App\Core\Filters\SubdivisionsFilter;
use App\Core\Traits\HasSorting;
use App\Modules\Admin\Subdivisions\Models\Subdivision;
use App\Modules\Admin\Subdivisions\Requests\ListSubdivisionsRequest;
use App\Modules\Admin\Subdivisions\Requests\SubdivisionsRequest;
use Illuminate\Pagination\LengthAwarePaginator;

class SubdivisionsService
{
    use HasSorting;

    public function getSubdivisions(ListSubdivisionsRequest $request, SubdivisionsFilter $filter): LengthAwarePaginator
    {
        $query = Subdivision::filter($filter);
        if (! $request->user()->canDo('SUPER_ADMINISTRATOR')) {
            $query->whereIn('id', $request->user()->getAvailableSubdivisionIds());
        }
        $this->applySorting($query, $request->input('sort') ?: 'id');

        return $query->orderBy('id')->paginate($request->integer('page_size', 20));
    }

    public function save(SubdivisionsRequest|\Illuminate\Http\Request $request, Subdivision $subdivision): Subdivision
    {
        $subdivision->fill($request->only($subdivision->getFillable()));
        $subdivision->save();

        return $subdivision;
    }
}
