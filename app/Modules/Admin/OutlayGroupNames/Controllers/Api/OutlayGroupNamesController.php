<?php

namespace App\Modules\Admin\OutlayGroupNames\Controllers\Api;

use App\Core\Filters\OutlayGroupNamesFilter;
use App\Http\Controllers\Controller;
use App\Modules\Admin\OutlayGroupNames\Models\OutlayGroupName;
use App\Modules\Admin\OutlayGroupNames\Requests\ListOutlayGroupNamesRequest;
use App\Modules\Admin\OutlayGroupNames\Requests\OutlayGroupNamesRequest;
use App\Modules\Admin\OutlayGroupNames\Services\OutlayGroupNamesService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;

class OutlayGroupNamesController extends Controller
{
    public function __construct(
        private readonly OutlayGroupNamesService $service
    ) {
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(
        ListOutlayGroupNamesRequest $request,
        OutlayGroupNamesFilter $filter
    ): JsonResponse
    {
        $this->authorize('view', new OutlayGroupName());

        return ResponseService::sendJsonResponse(true, 200, [], [
            'items' => $this->service->getOutlayGroupNames($request, $filter),
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
     * @param  OutlayGroupNamesRequest  $request
     * @return JsonResponse
     */
    public function store(OutlayGroupNamesRequest $request): JsonResponse
    {
        $this->authorize('manage', new OutlayGroupName());

        $outlayGroupName = $this->service->save($request, new OutlayGroupName());

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $outlayGroupName->load('subdivision')->toArray(),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\Admin\OutlayGroupNames\Models\OutlayGroupName  $outlayGroupName
     * @return JsonResponse
     */
    public function show(OutlayGroupName $outlayGroupName): JsonResponse
    {
        $this->authorize('view', $outlayGroupName);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $outlayGroupName->load('subdivision'),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\Admin\OutlayGroupNames\Models\OutlayGroupName  $outlayGroupName
     * @return \Illuminate\Http\Response
     */
    public function edit(OutlayGroupName $outlayGroupName)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  OutlayGroupNamesRequest  $request
     * @param  \App\Modules\Admin\OutlayGroupNames\Models\OutlayGroupName  $outlayGroupName
     * @return JsonResponse
     */
    public function update(
        OutlayGroupNamesRequest $request,
        OutlayGroupName $outlayGroupName
    ): JsonResponse
    {
        $this->authorize('manage', $outlayGroupName);

        $outlayGroupName = $this->service->save($request, $outlayGroupName);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $outlayGroupName->load('subdivision')->toArray(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\Admin\OutlayGroupNames\Models\OutlayGroupName  $outlayGroupName
     * @return JsonResponse
     */
    public function destroy(OutlayGroupName $outlayGroupName): JsonResponse
    {
        $this->authorize('manage', $outlayGroupName);

        $outlayGroupName->delete();

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $outlayGroupName,
        ]);
    }
}
