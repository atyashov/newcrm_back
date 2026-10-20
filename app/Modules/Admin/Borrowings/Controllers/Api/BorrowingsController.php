<?php

namespace App\Modules\Admin\Borrowings\Controllers\Api;

use App\Core\Filters\BorrowingsFilter;
use App\Http\Controllers\Controller;
use App\Modules\Admin\Borrowings\Models\Borrowing;
use App\Modules\Admin\Borrowings\Requests\BorrowingsRequest;
use App\Modules\Admin\Borrowings\Services\BorrowingsService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BorrowingsController extends Controller
{
    public function __construct(
        private readonly BorrowingsService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, BorrowingsFilter $filter): JsonResponse
    {
        $this->authorize('view', new Borrowing);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'items' => $this->service->getBorrowings(
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
    public function store(BorrowingsRequest $request): JsonResponse
    {
        $this->authorize('manage', new Borrowing);

        $borrowing = $this->service->save($request, new Borrowing);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $borrowing->load($this->relations())->toArray(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Borrowing $borrowing): JsonResponse
    {
        $this->authorize('view', $borrowing);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $borrowing->load($this->relations()),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Borrowing $borrowing)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BorrowingsRequest $request, Borrowing $borrowing): JsonResponse
    {
        $this->authorize('manage', $borrowing);

        $borrowing = $this->service->save($request, $borrowing);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $borrowing->load($this->relations())->toArray(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Borrowing $borrowing): JsonResponse
    {
        $this->authorize('manage', $borrowing);

        $borrowing = $this->service->delete($borrowing, $request->user()?->id);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $borrowing,
        ]);
    }

    private function relations(): array
    {
        return [
            'subdivisionSender',
            'subdivisionReceiver',
            'cashBoxSender.paymentMethod',
            'cashBoxReceiver.paymentMethod',
        ];
    }
}
