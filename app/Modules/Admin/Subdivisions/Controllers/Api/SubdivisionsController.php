<?php

namespace App\Modules\Admin\Subdivisions\Controllers\Api;

use App\Modules\Admin\Subdivisions\Models\Subdivision;
use App\Modules\Admin\Subdivisions\Requests\SubdivisionsRequest;
use App\Modules\Admin\Subdivisions\Requests\ListSubdivisionsRequest;
use App\Core\Filters\SubdivisionsFilter;
use App\Modules\Admin\Subdivisions\Services\SubdivisionsService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SubdivisionsController extends Controller
{

    private $service;

    public function __construct(SubdivisionsService $subdivisionsService)
    {
        $this->service = $subdivisionsService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(ListSubdivisionsRequest $request, SubdivisionsFilter $filter)
    {
        $this->authorize('view', Subdivision::class);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'items' => $this->service->getSubdivisions($request, $filter)
        ]);
    }

    /**
     * Create of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(SubdivisionsRequest $request)
    {
        $this->authorize('manage', Subdivision::class);
        $subdivision = $this->service->save($request, new Subdivision());

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $subdivision->toArray()
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\Admin\Subdivisions\Models\Subdivision  $subdivision
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Subdivision $subdivision)
    {
        $this->authorize('view', $subdivision);
        abort_unless(request()->user()->hasSubdivisionAccess($subdivision->id), 403);
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $subdivision
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\Admin\Subdivisions\Models\Subdivision  $subdivision
     * @return \Illuminate\Http\Response
     */
    public function edit(Subdivision $subdivision)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\Admin\Subdivisions\Models\Subdivision  $subdivision
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(SubdivisionsRequest $request, Subdivision $subdivision)
    {
        $this->authorize('manage', $subdivision);
        abort_unless($request->user()->hasSubdivisionAccess($subdivision->id), 403);
        $subdivision = $this->service->save($request, $subdivision);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $subdivision->toArray()
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\Admin\Subdivisions\Models\Subdivision  $subdivision
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Subdivision $subdivision)
    {
        $this->authorize('delete', $subdivision);
        abort_unless(request()->user()->hasSubdivisionAccess($subdivision->id), 403);
        $subdivision->delete();
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $subdivision
        ]);
    }
}
