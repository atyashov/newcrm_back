<?php

namespace App\Modules\Admin\Expenses\Controllers\Api;

use App\Core\Filters\ExpensesFilter;
use App\Http\Controllers\Controller;
use App\Modules\Admin\Expenses\Models\Expense;
use App\Modules\Admin\Expenses\Requests\ExpensesRequest;
use App\Modules\Admin\Expenses\Services\ExpensesService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpensesController extends Controller
{
    public function __construct(
        private readonly ExpensesService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, ExpensesFilter $filter): JsonResponse
    {
        $this->authorize('view', new Expense);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'items' => $this->service->getExpenses(
                $request->get('page_size') ?? $request->get('per_page') ?? 20,
                $filter,
                $request->get('sort'),
                $request->user()->getAvailableSubdivisionIds(),
            ),
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
     */
    public function store(ExpensesRequest $request): JsonResponse
    {
        $this->authorize('manage', new Expense);

        $expense = $this->service->save($request, new Expense);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $expense->load(['subdivision', 'expenseGroup', 'cashBox.paymentMethod'])->toArray(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense): JsonResponse
    {
        $this->authorize('view', $expense);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $expense->load(['subdivision', 'expenseGroup', 'cashBox.paymentMethod']),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Expense $expense)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ExpensesRequest $request, Expense $expense): JsonResponse
    {
        $this->authorize('manage', $expense);

        $expense = $this->service->save($request, $expense);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $expense->load(['subdivision', 'expenseGroup', 'cashBox.paymentMethod'])->toArray(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Expense $expense): JsonResponse
    {
        $this->authorize('manage', $expense);

        $expense = $this->service->delete($expense, $request->user()?->id);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $expense,
        ]);
    }
}
