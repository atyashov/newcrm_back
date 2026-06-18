<?php

namespace App\Modules\Admin\Subdivisions\Controllers\Api;

use App\Modules\Admin\Subdivisions\Models\Subdivision;
use App\Modules\Admin\Subdivisions\Requests\SubdivisionsRequest;
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
    public function index()
    {
        $this->authorize('view', Subdivision::class);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'items' => $this->service->getSubdivisions()
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
        $subdivision->delete();
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $subdivision
        ]);
    }
}
