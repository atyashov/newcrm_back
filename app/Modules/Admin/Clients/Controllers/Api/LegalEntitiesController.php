<?php

namespace App\Modules\Admin\Clients\Controllers\Api;

use App\Core\Filters\LegalEntityFilter;
use App\Http\Controllers\Controller;
use App\Modules\Admin\Clients\Models\LegalEntity;
use App\Modules\Admin\Clients\Requests\LegalEntityRequest;
use App\Modules\Admin\Clients\Services\LegalEntityService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;

class LegalEntitiesController extends Controller
{

    private $service;

    public function __construct(LegalEntityService $legalEntityService)
    {
        $this->service = $legalEntityService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, LegalEntityFilter $filter)
    {
        $this->authorize('view', new LegalEntity());
        return ResponseService::sendJsonResponse(true, 200, [], [
            'items' => $this->service->getLegalEntities($request->get('page_size') ?? 20, $filter, $request->get('sort'))
        ]);
    }

    /**
     * Create of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
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
    public function store(LegalEntityRequest $request)
    {
        $legalEntity = $this->service->save($request, new LegalEntity());
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $legalEntity->toArray()
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\Admin\Clients\Models\LegalEntity  $legalEntity
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(LegalEntity $legalEntity)
    {
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $legalEntity
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\Admin\Clients\Models\LegalEntity  $legalEntity
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Client $client)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\Admin\Clients\Models\LegalEntity  $legalEntity
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(LegalEntityRequest $request, LegalEntity $legalEntity)
    {
        $legalEntity = $this->service->save($request, $legalEntity);
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $legalEntity->toArray()
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\Admin\Clients\Models\LegalEntity  $legalEntity
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(LegalEntity $legalEntity)
    {
        $legalEntity->delete();
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $legalEntity
        ]);
    }
}
