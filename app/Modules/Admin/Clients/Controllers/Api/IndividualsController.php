<?php

namespace App\Modules\Admin\Clients\Controllers\Api;

use App\Core\Filters\IndividualFilter;
use App\Modules\Admin\Clients\Models\Individual;
use App\Modules\Admin\Clients\Requests\IndividualRequest;
use App\Modules\Admin\Clients\Services\IndividualService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class IndividualsController extends Controller
{

    public function __construct(
        private readonly IndividualService $service
    )
    {
    }

    /**
     * Display a listing of the resource.
     *
     */
    public function index(Request $request, IndividualFilter $filter) : JsonResponse
    {

        $this->authorize('view', new Individual());
        return ResponseService::sendJsonResponse(true, 200, [], [
            'items' => $this->service
                ->getIndividuals($request->get('page_size') ?? 10, $filter, $request->get('sort'))
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
     * @param  IndividualRequest  $request
     * @return JsonResponse
     */
    public function store(IndividualRequest $request)
    {
        $individual = $this->service->save($request, new Individual());
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $individual->toArray()
        ]);

    }

    /**
     * Display the specified resource.
     *
     * @param  Individual  $individual
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Individual $individual)
    {
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $individual
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\Admin\Clients\Models\Individual  $individual
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Client $client)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  IndividualRequest  $request
     * @param  Individual  $individual
     * @return JsonResponse
     */
    public function update(IndividualRequest $request, Individual $individual)
    {
        $individual = $this->service->save($request, $individual);
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $individual->toArray()
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Individual  $individual
     * @return JsonResponse
     */
    public function destroy(Individual $individual)
    {
        $individual->delete();
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $individual
        ]);
    }
}
