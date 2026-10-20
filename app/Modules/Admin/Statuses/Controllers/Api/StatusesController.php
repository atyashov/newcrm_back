<?php

namespace App\Modules\Admin\Statuses\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Statuses\Models\Status;
use App\Modules\Admin\Statuses\Requests\StatusesRequest;
use App\Modules\Admin\Statuses\Services\StatusesService;
use App\Services\Response\ResponseService;

class StatusesController extends Controller
{
    private $service;

    public function __construct(StatusesService $statusesService)
    {
        $this->service = $statusesService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $this->authorize('view', new Status);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'items' => $this->service->getStatuses(),
        ]);
    }

    //    public function withOutlays(StatusesRequest $request)
    //    {
    //        $this->authorize('view', Status::class);
    //
    //        return ResponseService::sendJsonResponse(true, 200, [], [
    //            'items' => $this->service->getWithOutlays($request)
    //        ]);
    //    }

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
    public function store(StatusesRequest $request)
    {
        $this->authorize('manage', new Status);
        $status = $this->service->save($request, new Status);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $status->toArray(),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Status $status)
    {
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $status,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Status $status)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(StatusesRequest $request, Status $status)
    {
        $this->authorize('manage', $status);
        $status = $this->service->save($request, $status);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $status->toArray(),
        ]);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Status $status)
    {
        $this->authorize('manage', $status);
        $status->delete();

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $status,
        ]);
    }
}
