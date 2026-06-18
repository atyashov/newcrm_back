<?php

namespace App\Modules\Admin\OutlayGroups\Controllers\Api;

use App\Modules\Admin\OutlayGroups\Models\OutlayGroup;
use App\Modules\Admin\OutlayGroups\Services\OutlayGroupsService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class OutlayGroupsController extends Controller
{

    private $service;

    public function __construct(OutlayGroupsService $outlayGroupsService)
    {
        $this->service = $outlayGroupsService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        return ResponseService::sendJsonResponse(true, 200, [], [
            'items' => $this->service->getOutlayGroups($request)
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
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\Admin\OutlayGroups\Models\OutlayGroup  $outlayGroup
     * @return \Illuminate\Http\Response
     */
    public function show(OutlayGroup $outlayGroup)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\Admin\OutlayGroups\Models\OutlayGroup  $outlayGroup
     * @return \Illuminate\Http\Response
     */
    public function edit(OutlayGroup $outlayGroup)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\Admin\OutlayGroups\Models\OutlayGroup  $outlayGroup
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, OutlayGroup $outlayGroup)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\Admin\OutlayGroups\Models\OutlayGroup  $outlayGroup
     * @return \Illuminate\Http\Response
     */
    public function destroy(OutlayGroup $outlayGroup)
    {
        //
    }
}
