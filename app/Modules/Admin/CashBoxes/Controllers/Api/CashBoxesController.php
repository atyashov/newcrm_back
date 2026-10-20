<?php

namespace App\Modules\Admin\CashBoxes\Controllers\Api;

use App\Core\Filters\CashBoxesFilter;
use App\Http\Controllers\Controller;
use App\Modules\Admin\CashBoxes\Models\CashBox;
use App\Modules\Admin\CashBoxes\Requests\CashBoxesRequest;
use App\Modules\Admin\CashBoxes\Requests\CashBoxesSummaryRequest;
use App\Modules\Admin\CashBoxes\Services\CashBoxesSummaryService;
use App\Modules\Admin\CashBoxes\Requests\ListCashOperationsRequest;
use App\Modules\Admin\CashBoxes\Services\CashOperationsService;
use App\Modules\Admin\CashBoxes\Services\CashBoxesService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashBoxesController extends Controller
{
    public function __construct(
        private readonly CashBoxesService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, CashBoxesFilter $filter): JsonResponse
    {
        $this->authorize('view', new CashBox);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'items' => $this->service->getCashBoxes(
                $request->get('page_size') ?? 20,
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
    public function store(CashBoxesRequest $request): JsonResponse
    {
        $this->authorize('manage', new CashBox);

        $cashBox = $this->service->save($request, new CashBox);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $cashBox->load(['paymentMethod', 'subdivision'])->toArray(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(CashBox $cashBox): JsonResponse
    {
        $this->authorize('view', $cashBox);
        abort_unless($this->canAccess(request()->user(), $cashBox), 403);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $cashBox->load(['paymentMethod', 'subdivision']),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(CashBox $cashBox)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CashBoxesRequest $request, CashBox $cashBox): JsonResponse
    {
        $this->authorize('manage', $cashBox);
        abort_unless($this->canAccess($request->user(), $cashBox), 403);

        $cashBox = $this->service->save($request, $cashBox);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $cashBox->load(['paymentMethod', 'subdivision'])->toArray(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CashBox $cashBox): JsonResponse
    {
        $this->authorize('manage', $cashBox);
        abort_unless($this->canAccess(request()->user(), $cashBox), 403);

        $cashBox->delete();

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $cashBox,
        ]);
    }

    public function transactions(Request $request, CashBox $cashBox): JsonResponse
    {
        $this->authorize('view', $cashBox);
        abort_unless($this->canAccess($request->user(), $cashBox), 403);

        $items = $cashBox->transactions()
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(min(max($request->integer('per_page', 20), 1), 100));

        return ResponseService::sendJsonResponse(true, 200, [], ['items' => $items]);
    }

    public function operations(ListCashOperationsRequest $request, CashOperationsService $service): JsonResponse
    {
        return ResponseService::sendJsonResponse(true, 200, [], $service->list($request));
    }

    public function summary(CashBoxesSummaryRequest $request, CashBoxesSummaryService $service): JsonResponse
    {
        $this->authorize('view', new CashBox);
        abort_unless($request->user()->hasSubdivisionAccess($request->integer('subdivision_id')), 403);

        return ResponseService::sendJsonResponse(true, 200, [], $service->summary(
            $request->integer('subdivision_id'),
            $request->validated('date_start_from'),
            $request->validated('date_start_to'),
        ));
    }

    private function canAccess($user, CashBox $cashBox): bool
    {
        return $cashBox->subdivision_id === null
            || $user->hasSubdivisionAccess($cashBox->subdivision_id);
    }
}
