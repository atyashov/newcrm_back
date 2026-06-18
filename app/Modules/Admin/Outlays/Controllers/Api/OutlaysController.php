<?php

namespace App\Modules\Admin\Outlays\Controllers\Api;

use App\Modules\Admin\Outlays\Models\Outlay;
use App\Modules\Admin\Outlays\Requests\OutlayRequest;
use App\Modules\Admin\Outlays\Services\OutlayService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OutlaysController extends Controller
{

    private $service;

    public function __construct(OutlayService $outlayService)
    {
        $this->service = $outlayService;
    }

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
//        $this->authorize('view', new Outlay());

        return ResponseService::sendJsonResponse(true, 200, [], [
            'items' => $this->service->getOutlays($request)
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
    public function store(OutlayRequest $request): \Illuminate\Http\JsonResponse
    {
        $outlay = $this->service->save($request, new Outlay());

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $outlay->toArray()
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\Admin\Outlays\Models\Outlay  $outlay
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Outlay $outlay): \Illuminate\Http\JsonResponse
    {
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $outlay
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\Admin\Outlays\Models\Outlay  $outlay
     * @return \Illuminate\Http\Response
     */
    public function edit(Outlay $outlay)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\Admin\Outlays\Models\Outlay  $outlay
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(OutlayRequest $request, Outlay $outlay): \Illuminate\Http\Response
    {
        $outlay = $this->service->save($request, $outlay);
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $outlay->toArray()
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\Admin\Outlays\Models\Outlay  $outlay
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Outlay $outlay): \Illuminate\Http\Response
    {
        $outlay->delete();
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $outlay
        ]);
    }
}
