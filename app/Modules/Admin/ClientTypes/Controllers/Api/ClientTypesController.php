<?php

namespace App\Modules\Admin\ClientTypes\Controllers\Api;

use App\Modules\Admin\ClientTypes\Models\ClientType;
use App\Modules\Admin\ClientTypes\Requests\ClientTypesRequest;
use App\Modules\Admin\ClientTypes\Services\ClientTypesService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ClientTypesController extends Controller
{

    private $service;

    public function __construct(ClientTypesService $clientTypesService)
    {
        $this->service = $clientTypesService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $this->authorize('view', new ClientType());

        return ResponseService::sendJsonResponse(true, 200, [], [
            'items' => $this->service->getClientTypes()
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
    public function store(ClientTypesRequest $request)
    {
        $clientType = $this->service->save($request, new ClientType());

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $clientType->toArray()
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\Admin\ClientTypes\Models\ClientType  $clientType
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(ClientType $clientType)
    {
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $clientType
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\Admin\ClientTypes\Models\ClientType  $clientType
     * @return \Illuminate\Http\Response
     */
    public function edit(ClientType $clientType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\Admin\ClientTypes\Models\ClientType  $clientType
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, ClientType $clientType)
    {
        $clientType = $this->service->save($request, $clientType);
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $clientType->toArray()
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\Admin\ClientTypes\Models\ClientType  $clientType
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(ClientType $clientType)
    {
        $clientType->delete();
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $clientType
        ]);
    }
}
