<?php

namespace App\Modules\Admin\ExpenseGroups\Controllers\Api;

use App\Modules\Admin\ExpenseGroups\Models\ExpenseGroup;
use App\Modules\Admin\ExpenseGroups\Requests\ExpenseGroupsRequest;
use App\Modules\Admin\ExpenseGroups\Services\ExpenseGroupsService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class ExpenseGroupsController extends Controller
{
    public function __construct(
        private readonly ExpenseGroupsService $service
    ) {
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $this->authorize('view', new ExpenseGroup());

        return ResponseService::sendJsonResponse(true, 200, [], [
            'items' => $this->service->getExpenseGroups(),
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
     * @param  ExpenseGroupsRequest  $request
     * @return JsonResponse
     */
    public function store(ExpenseGroupsRequest $request): JsonResponse
    {
        $expenseGroup = $this->service->save($request, new ExpenseGroup());

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $expenseGroup->toArray(),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\Admin\ExpenseGroups\Models\ExpenseGroup  $expenseGroup
     * @return JsonResponse
     */
    public function show(ExpenseGroup $expenseGroup): JsonResponse
    {
        $this->authorize('view', $expenseGroup);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $expenseGroup->load('children'),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\Admin\ExpenseGroups\Models\ExpenseGroup  $expenseGroup
     * @return \Illuminate\Http\Response
     */
    public function edit(ExpenseGroup $expenseGroup)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  ExpenseGroupsRequest  $request
     * @param  \App\Modules\Admin\ExpenseGroups\Models\ExpenseGroup  $expenseGroup
     * @return JsonResponse
     */
    public function update(ExpenseGroupsRequest $request, ExpenseGroup $expenseGroup): JsonResponse
    {
        $expenseGroup = $this->service->save($request, $expenseGroup);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $expenseGroup->toArray(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\Admin\ExpenseGroups\Models\ExpenseGroup  $expenseGroup
     * @return JsonResponse
     */
    public function destroy(ExpenseGroup $expenseGroup): JsonResponse
    {
        $this->authorize('delete', $expenseGroup);

        $expenseGroup->delete();

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $expenseGroup,
        ]);
    }
}
