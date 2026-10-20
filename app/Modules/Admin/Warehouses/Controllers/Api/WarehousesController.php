<?php

namespace App\Modules\Admin\Warehouses\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Warehouses\Models\ProductRental;
use App\Modules\Admin\Warehouses\Models\ProductStock;
use App\Modules\Admin\Warehouses\Requests\AdjustStockRequest;
use App\Modules\Admin\Warehouses\Requests\ListRentalsRequest;
use App\Modules\Admin\Warehouses\Requests\ListStocksRequest;
use App\Modules\Admin\Warehouses\Requests\RentalStatusRequest;
use App\Modules\Admin\Warehouses\Services\WarehouseService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehousesController extends Controller
{
    public function __construct(private readonly WarehouseService $service) {}

    public function stocks(ListStocksRequest $request): JsonResponse
    {
        $this->authorize('view', new ProductStock);
        $sort = $request->validated('sort', 'title');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        $column = $column === 'title' ? 'products.title' : 'product_stocks.'.$column;

        $items = ProductStock::query()
            ->select('product_stocks.*')
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->with(['product', 'subdivision'])
            ->where('product_stocks.subdivision_id', $request->integer('subdivision_id'))
            ->when($request->filled('search'), fn ($query) => $query
                ->where('products.title', 'like', '%'.$request->string('search').'%'))
            ->orderBy($column, $direction)
            ->orderBy('product_stocks.id')
            ->paginate($request->integer('per_page', 20));

        return ResponseService::sendJsonResponse(true, 200, [], ['items' => $items]);
    }

    public function rentals(ListRentalsRequest $request): JsonResponse
    {
        $this->authorize('view', new ProductStock);

        $items = ProductRental::query()
            ->with(['product', 'outlay', 'subdivision'])
            ->where('subdivision_id', $request->integer('subdivision_id'))
            ->when($request->filled('product_id'), fn ($query) => $query->where('product_id', $request->integer('product_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->validated('status')))
            ->when($request->filled('starts_from'), fn ($query) => $query->where('starts_at', '>=', $request->validated('starts_from')))
            ->when($request->filled('ends_to'), fn ($query) => $query->where('ends_at', '<=', $request->validated('ends_to')))
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 20));

        return ResponseService::sendJsonResponse(true, 200, [], ['items' => $items]);
    }

    public function movements(Request $request, ProductStock $stock): JsonResponse
    {
        $this->authorize('view', $stock);
        abort_unless($request->user()->hasSubdivisionAccess($stock->subdivision_id), 403);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'items' => $stock->movements()->with('stock.product')
                ->orderByDesc('occurred_at')->orderByDesc('id')
                ->paginate(min(max($request->integer('per_page', 20), 1), 100)),
        ]);
    }

    public function adjust(AdjustStockRequest $request, ProductStock $stock): JsonResponse
    {
        $this->authorize('manage', $stock);
        abort_unless((int) $stock->subdivision_id === $request->integer('subdivision_id'), 404);

        $stock = $this->service->adjust(
            $stock,
            $request->validated('quantity_delta'),
            $request->user()->id,
            $request->validated('comment'),
        );

        return ResponseService::sendJsonResponse(true, 200, [], ['item' => $stock]);
    }

    public function updateRentalStatus(RentalStatusRequest $request, ProductRental $rental): JsonResponse
    {
        $this->authorize('manage', new ProductStock);
        abort_unless((int) $rental->subdivision_id === $request->integer('subdivision_id'), 404);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $this->service->updateRentalStatus($rental, $request->validated('status')),
        ]);
    }
}
