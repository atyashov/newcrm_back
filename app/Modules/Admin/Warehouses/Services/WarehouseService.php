<?php

namespace App\Modules\Admin\Warehouses\Services;

use App\Modules\Admin\Outlays\Models\Outlay;
use App\Modules\Admin\Products\Models\Product;
use App\Modules\Admin\Warehouses\Models\ProductRental;
use App\Modules\Admin\Warehouses\Models\ProductStock;
use App\Modules\Admin\Warehouses\Models\StockMovement;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WarehouseService
{
    public function syncOutlay(Outlay $outlay, ?int $userId): void
    {
        $outlay->loadMissing(['status', 'groups.items.product']);
        $active = (bool) $outlay->status?->affects_stock;
        $items = $outlay->groups->flatMap->items;
        $desiredMovements = [];
        $desiredRentals = [];

        if ($active) {
            foreach ($items as $item) {
                $product = $item->product;

                if (! $product || $product->unlimited || $product->is_service) {
                    continue;
                }

                $stock = $this->stockFor($product->id, $outlay->subdivision_id, $product->quantity);

                if ($item->is_rent) {
                    if (! $outlay->return_date) {
                        throw ValidationException::withMessages([
                            'return_date' => 'Return date is required for rental products.',
                        ]);
                    }

                    $desiredRentals[$item->id] = [
                        'stock' => $stock,
                        'item' => $item,
                    ];

                    continue;
                }

                $desiredMovements[$this->movementKey($stock->id, $item->id)] = [
                    'stock' => $stock,
                    'item' => $item,
                    'quantity' => -$this->units($item->quantity),
                ];
            }
        }

        ProductStock::query()
            ->whereKey(collect($desiredMovements)->pluck('stock.id')
                ->merge(collect($desiredRentals)->pluck('stock.id'))->unique())
            ->lockForUpdate()
            ->get();

        $this->validateRentalAvailability($outlay, $desiredRentals);
        $this->reconcileMovements($outlay, $desiredMovements, $userId);
        $this->reconcileRentals($outlay, $desiredRentals);
    }

    public function removeOutlay(Outlay $outlay, ?int $userId): void
    {
        $this->reconcileMovements($outlay, [], $userId);

        ProductRental::query()
            ->where('outlay_id', $outlay->id)
            ->whereIn('status', ProductRental::ACTIVE_STATUSES)
            ->update(['status' => 'cancelled', 'updated_at' => now()]);
    }

    public function availability(
        int $productId, int $subdivisionId, mixed $startsAt, mixed $endsAt, ?int $outlayId = null,
    ): ?string {
        return $this->batchAvailability([$productId], $subdivisionId, $startsAt, $endsAt, $outlayId)[0]['available_quantity'];
    }

    public function batchAvailability(
        array $productIds, int $subdivisionId, mixed $startsAt, mixed $endsAt, ?int $outlayId = null,
    ): array {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));
        if ($productIds === []) {
            return [];
        }

        if ($outlayId !== null && ! Outlay::query()->whereKey($outlayId)->where('subdivision_id', $subdivisionId)->exists()) {
            throw ValidationException::withMessages(['outlay_id' => 'Outlay does not belong to this subdivision.']);
        }

        $products = Product::query()->whereKey($productIds)
            ->whereHas('categories', fn ($query) => $query->where('subdivision_id', $subdivisionId))
            ->get()->keyBy('id');
        if ($products->count() !== count($productIds)) {
            throw ValidationException::withMessages(['product_ids' => 'Products must belong to this subdivision.']);
        }
        if ($endsAt === null && $products->contains(fn ($product) => $product->is_rent && ! $product->unlimited && ! $product->is_service)) {
            throw ValidationException::withMessages(['ends_at' => 'Return date is required for rental products.']);
        }

        $stocks = ProductStock::query()->whereIn('product_id', $productIds)
            ->where('subdivision_id', $subdivisionId)
            ->get()->keyBy('product_id');
        $occupied = $this->occupiedQuantities($productIds, $subdivisionId, $startsAt, $endsAt ?? $startsAt, $outlayId);
        $ownMovements = $outlayId === null ? collect() : StockMovement::query()
            ->join('product_stocks', 'product_stocks.id', '=', 'stock_movements.product_stock_id')
            ->where('source_type', 'outlay')->where('source_id', $outlayId)
            ->where('product_stocks.subdivision_id', $subdivisionId)
            ->whereIn('product_stocks.product_id', $productIds)
            ->selectRaw('product_stocks.product_id, SUM(stock_movements.quantity) as net_quantity')
            ->groupBy('product_stocks.product_id')->pluck('net_quantity', 'product_id');

        $result = [];
        foreach ($productIds as $productId) {
            $product = $products->get($productId);
            $unlimited = (bool) ($product->unlimited || $product->is_service);
            $ownConsumed = max(0, -$this->units($ownMovements->get($productId, 0)));
            $total = $this->units($stocks->get($productId)?->quantity ?? $product->quantity) + $ownConsumed;
            $reserved = $unlimited ? 0 : ($occupied[$productId] ?? 0);
            $result[] = [
                'product_id' => $productId,
                'is_rent' => (bool) $product->is_rent,
                'unlimited' => (bool) $product->unlimited,
                'is_service' => (bool) $product->is_service,
                'total_quantity' => $this->quantity($total),
                'occupied_quantity' => $this->quantity($reserved),
                'available_quantity' => $unlimited ? null : $this->quantity(max(0, $total - $reserved)),
            ];
        }

        return $result;
    }

    private function occupiedQuantities(
        array $productIds, int $subdivisionId, mixed $startsAt, mixed $endsAt,
        ?int $outlayId = null, bool $lock = false,
    ): array {
        $start = CarbonImmutable::parse($startsAt);
        $end = CarbonImmutable::parse($endsAt);
        $rentals = ProductRental::query()->whereIn('product_id', $productIds)
            ->where('subdivision_id', $subdivisionId)
            ->whereIn('status', ProductRental::ACTIVE_STATUSES)
            ->where('starts_at', '<=', $end->format('Y-m-d H:i:s'))
            ->where('ends_at', '>=', $start->format('Y-m-d H:i:s'))
            ->when($outlayId !== null, fn ($query) => $query
                ->where(fn ($query) => $query->whereNull('outlay_id')->orWhere('outlay_id', '!=', $outlayId)))
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->get(['product_id', 'quantity', 'starts_at', 'ends_at']);

        $events = [];
        foreach ($rentals as $rental) {
            $from = max($start->timestamp, $rental->starts_at->timestamp);
            $to = min($end->timestamp, $rental->ends_at->timestamp);
            $units = $this->units($rental->quantity);
            $events[$rental->product_id][$from]['start'] = ($events[$rental->product_id][$from]['start'] ?? 0) + $units;
            $events[$rental->product_id][$to]['end'] = ($events[$rental->product_id][$to]['end'] ?? 0) + $units;
        }

        $occupied = [];
        foreach ($events as $productId => $timeline) {
            ksort($timeline, SORT_NUMERIC);
            $current = 0;
            $peak = 0;
            // Boundaries are inclusive: starts at the same instant count before returns.
            foreach ($timeline as $event) {
                $current += $event['start'] ?? 0;
                $peak = max($peak, $current);
                $current -= $event['end'] ?? 0;
            }
            $occupied[$productId] = $peak;
        }

        return $occupied;
    }

    public function adjust(ProductStock $stock, mixed $quantityDelta, int $userId, ?string $comment): ProductStock
    {
        return DB::transaction(function () use ($stock, $quantityDelta, $userId, $comment) {
            $stock = ProductStock::query()->lockForUpdate()->findOrFail($stock->id);
            $delta = $this->units($quantityDelta);
            $result = $this->units($stock->quantity) + $delta;

            if ($result < 0) {
                throw ValidationException::withMessages(['quantity_delta' => 'Stock quantity cannot become negative.']);
            }

            StockMovement::query()->create([
                'product_stock_id' => $stock->id,
                'source_type' => 'manual',
                'source_id' => $userId,
                'source_line' => (string) Str::uuid(),
                'type' => 'manual_adjustment',
                'quantity' => $this->quantity($delta),
                'occurred_at' => now(),
                'user_id' => $userId,
                'comment' => $comment,
            ]);

            $stock->quantity = $this->quantity($result);
            $stock->save();

            return $stock->fresh(['product', 'subdivision']);
        });
    }

    public function updateRentalStatus(ProductRental $rental, string $status): ProductRental
    {
        return DB::transaction(function () use ($rental, $status) {
            $rental = ProductRental::query()->lockForUpdate()->findOrFail($rental->id);
            $transitions = [
                'reserved' => ['issued', 'cancelled'],
                'issued' => ['returned', 'cancelled'],
                'returned' => [],
                'cancelled' => [],
            ];

            if ($status !== $rental->status && ! in_array($status, $transitions[$rental->status] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'Rental status transition is not allowed.']);
            }

            $rental->status = $status;
            $rental->save();

            return $rental->fresh(['product', 'outlay', 'subdivision']);
        });
    }

    private function validateRentalAvailability(Outlay $outlay, array $desired): void
    {
        $grouped = collect($desired)->groupBy(fn (array $row) => $row['item']->product_id);
        if ($grouped->isEmpty()) {
            return;
        }
        $occupied = $this->occupiedQuantities(
            $grouped->keys()->all(), $outlay->subdivision_id,
            $outlay->execution_date, $outlay->return_date, $outlay->id, true,
        );
        $stocks = ProductStock::query()->whereKey(collect($desired)->pluck('stock.id')->unique())
            ->lockForUpdate()->get()->keyBy('product_id');

        foreach ($grouped as $productId => $rows) {
            $requested = $rows->sum(fn (array $row) => $this->units($row['item']->quantity));
            $stock = $stocks->get($productId);

            if ($requested + ($occupied[$productId] ?? 0) > $this->units($stock->quantity)) {
                throw ValidationException::withMessages([
                    'groups' => "Not enough rental product {$productId} for the selected period.",
                ]);
            }
        }
    }

    private function reconcileMovements(Outlay $outlay, array $desired, ?int $userId): void
    {
        $existingRows = StockMovement::query()
            ->where('source_type', 'outlay')
            ->where('source_id', $outlay->id)
            ->get();

        $stockIds = collect($desired)->pluck('stock.id')
            ->merge($existingRows->pluck('product_stock_id'))->unique();
        $stocks = ProductStock::query()->whereKey($stockIds)->lockForUpdate()->get()->keyBy('id');

        $existingRows = StockMovement::query()
            ->where('source_type', 'outlay')
            ->where('source_id', $outlay->id)
            ->get();
        $existing = $existingRows
            ->groupBy(fn (StockMovement $row) => $this->movementKey($row->product_stock_id, $row->source_line))
            ->map(fn (Collection $rows) => $rows->sum(fn (StockMovement $row) => $this->units($row->quantity)));
        $keys = collect(array_keys($desired))->merge($existing->keys())->unique();
        $deltas = [];
        $stockDeltas = [];
        foreach ($keys as $key) {
            $delta = ($desired[$key]['quantity'] ?? 0) - ($existing->get($key) ?? 0);
            $stockId = (int) explode(':', $key, 2)[0];
            $deltas[$key] = $delta;
            $stockDeltas[$stockId] = ($stockDeltas[$stockId] ?? 0) + $delta;
        }

        foreach ($stockDeltas as $stockId => $delta) {
            $stock = $stocks->get($stockId);
            if ($this->units($stock->quantity) + $delta < 0) {
                throw ValidationException::withMessages([
                    'groups' => "Not enough product {$stock->product_id} in stock.",
                ]);
            }
        }

        foreach ($keys as $key) {
            $desiredRow = $desired[$key] ?? null;
            $delta = $deltas[$key];

            if ($delta === 0) {
                continue;
            }

            [$stockId, $sourceLine] = explode(':', $key, 2);
            $stock = $stocks->get((int) $stockId);

            StockMovement::query()->create([
                'product_stock_id' => $stock->id,
                'source_type' => 'outlay',
                'source_id' => $outlay->id,
                'source_line' => $sourceLine,
                'type' => $delta < 0 ? 'outlay_consumption' : 'outlay_reversal',
                'quantity' => $this->quantity($delta),
                'occurred_at' => now(),
                'user_id' => $userId,
                'comment' => $desiredRow ? null : 'Automatic reversal',
            ]);

        }

        foreach ($stockDeltas as $stockId => $delta) {
            if ($delta === 0) {
                continue;
            }
            $stock = $stocks->get($stockId);
            $stock->quantity = $this->quantity($this->units($stock->quantity) + $delta);
            $stock->save();
        }
    }

    private function reconcileRentals(Outlay $outlay, array $desired): void
    {
        $existing = ProductRental::query()->where('outlay_id', $outlay->id)->get()->keyBy('outlay_item_id');

        foreach ($desired as $itemId => $row) {
            $rental = $existing->pull($itemId) ?? new ProductRental;
            $status = $rental->exists && in_array($rental->status, ['issued', 'returned'], true)
                ? $rental->status
                : 'reserved';

            $rental->fill([
                'outlay_id' => $outlay->id,
                'outlay_item_id' => $itemId,
                'product_id' => $row['item']->product_id,
                'subdivision_id' => $outlay->subdivision_id,
                'quantity' => $row['item']->quantity,
                'starts_at' => $outlay->execution_date,
                'ends_at' => $outlay->return_date,
                'status' => $status,
            ]);
            $rental->save();
        }

        ProductRental::query()
            ->whereKey($existing->pluck('id'))
            ->whereIn('status', ProductRental::ACTIVE_STATUSES)
            ->update(['status' => 'cancelled', 'updated_at' => now()]);
    }

    private function stockFor(int $productId, int $subdivisionId, mixed $initialQuantity): ProductStock
    {
        return ProductStock::query()->firstOrCreate(
            ['product_id' => $productId, 'subdivision_id' => $subdivisionId],
            ['quantity' => $initialQuantity]
        );
    }

    private function movementKey(int $stockId, int $itemId): string
    {
        return $stockId.':'.$itemId;
    }

    private function units(mixed $value): int
    {
        [$whole, $fraction] = array_pad(explode('.', (string) ($value ?? 0), 2), 2, '');
        $sign = str_starts_with($whole, '-') ? -1 : 1;
        $whole = ltrim($whole, '-');

        return $sign * ((int) $whole * 1000 + (int) str_pad(substr($fraction, 0, 3), 3, '0'));
    }

    private function quantity(int $units): string
    {
        $sign = $units < 0 ? '-' : '';
        $units = abs($units);

        return $sign.intdiv($units, 1000).'.'.str_pad((string) ($units % 1000), 3, '0', STR_PAD_LEFT);
    }
}
