<?php

namespace App\Modules\Admin\Outlays\Services;

use App\Core\Filters\OutlaysFilter;
use App\Core\Traits\HasSorting;
use App\Modules\Admin\CashBoxes\Models\CashBox;
use App\Modules\Admin\CashBoxes\Services\CashLedgerService;
use App\Modules\Admin\OutlayGroupNames\Models\OutlayGroupName;
use App\Modules\Admin\OutlayGroups\Models\OutlayItem;
use App\Modules\Admin\OutlayGroups\Models\OutlayItemStage;
use App\Modules\Admin\Outlays\Models\Outlay;
use App\Modules\Admin\Outlays\Requests\DeleteOutlayRequest;
use App\Modules\Admin\Outlays\Requests\ListOutlaysRequest;
use App\Modules\Admin\Outlays\Requests\OutlayRequest;
use App\Modules\Admin\Products\Models\Product;
use App\Modules\Admin\Warehouses\Services\WarehouseService;
use Brick\Math\BigInteger;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OutlayService
{
    use HasSorting;

    public function __construct(
        private readonly WarehouseService $warehouseService,
        private readonly CashLedgerService $cashLedgerService,
        private readonly OutlayAuditService $auditService,
    ) {}

    public function getOutlays(ListOutlaysRequest $request, OutlaysFilter $filter): LengthAwarePaginator
    {
        $pageSize = $request->get('page_size') ?? $request->get('per_page') ?? $request->get('pageSize') ?? 20;
        $sort = $request->get('sort');

        $filterFields = array_filter(array_keys($request->rules()), fn ($field) => ! str_contains($field, '.'));
        $filter = new OutlaysFilter(new \Illuminate\Http\Request($request->only($filterFields)));

        $outlayList = Outlay::filter($filter)
            ->whereIn('subdivision_id', $request->user()->getAvailableSubdivisionIds())
            ->with(Outlay::DISPLAY_RELATIONS);

        if ($sort) {
            $outlayList = $this->applySorting($outlayList, $sort);
            $outlayList->orderBy('id');
        } else {
            $outlayList
                ->orderBy('execution_date', 'ASC')
                ->orderBy('id', 'ASC');
        }

        $page = $outlayList->paginate($pageSize);

        if ($page->isNotEmpty()) {
            $stores = DB::table('outlay_groups')
                ->join('outlay_items', 'outlay_items.outlay_group_id', '=', 'outlay_groups.id')
                ->join('products', 'products.id', '=', 'outlay_items.product_id')
                ->join('stores', 'stores.id', '=', 'products.store_id')
                ->whereIn('outlay_groups.outlay_id', $page->getCollection()->modelKeys())
                ->leftJoin('outlay_item_stages', 'outlay_item_stages.outlay_item_id', '=', 'outlay_items.id')
                ->select('outlay_groups.outlay_id', 'stores.id', 'stores.title')
                ->selectRaw('COUNT(outlay_items.id) AS total')
                ->selectRaw(implode(', ', array_map(fn ($flag) => "SUM(CASE WHEN outlay_item_stages.$flag = 1 THEN 1 ELSE 0 END) AS $flag", OutlayItemStage::FLAGS)))
                ->groupBy('outlay_groups.outlay_id', 'stores.id', 'stores.title')
                ->orderBy('stores.title')->orderBy('stores.id')
                ->get()
                ->groupBy('outlay_id');

            foreach ($page->getCollection() as $outlay) {
                $outlay->withAssignmentIds();
                $outlay->setRelation('stores', $stores->get($outlay->id, collect())
                    ->map(fn ($store) => ['id' => $store->id, 'title' => $store->title,
                        'stages' => collect(['total', ...OutlayItemStage::FLAGS])->mapWithKeys(fn ($flag) => [$flag => (int) $store->{$flag}])->all()])
                    ->values());
            }
        }

        return $page;
    }

    public function save(OutlayRequest $request, Outlay $outlay): Outlay
    {
        $data = $request->validated();

        return DB::transaction(function () use ($request, $data, $outlay) {
            if ($outlay->exists) {
                $outlay = Outlay::query()->lockForUpdate()->findOrFail($outlay->id);
                abort_unless($request->user()->hasSubdivisionAccess($outlay->subdivision_id), 403);
                $this->ensure((int) $outlay->subdivision_id === (int) $data['subdivision_id'],
                    'subdivision_id', 'Changing the subdivision of an outlay is not supported.');
            }
            abort_unless($request->user()->hasSubdivisionAccess($data['subdivision_id']), 403);

            $auditBefore = $outlay->exists ? $this->auditService->snapshot($outlay) : null;

            $outlay->fill(Arr::except($data, ['groups', ...array_keys(Outlay::ASSIGNMENTS)]));
            $outlay->save();

            $this->syncAssignments($outlay, $data);

            $groupIds = [];
            $rows = [];
            $subtotal = 0;
            $products = Product::query()
                ->whereIn('id', collect($data['groups'])->flatMap(fn ($group) => array_column($group['items'], 'product_id')))
                ->whereHas('categories', fn ($query) => $query->where('subdivision_id', $outlay->subdivision_id))
                ->with('description')->get()->keyBy('id');

            foreach (array_values($data['groups']) as $g => $groupData) {
                $group = ! empty($groupData['id']) ? $outlay->groups()->find($groupData['id']) : $outlay->groups()->make();
                $this->ensure($group !== null, "groups.$g.id", 'Group does not belong to this outlay.');
                $this->ensure(OutlayGroupName::query()
                    ->whereKey($groupData['outlay_group_name_id'])->where('subdivision_id', $outlay->subdivision_id)->exists(),
                    "groups.$g.outlay_group_name_id", 'Group name does not belong to this subdivision.');
                $group->fill(['outlay_group_name_id' => $groupData['outlay_group_name_id'], 'position' => $g]);
                $group->save();
                $groupIds[] = $group->id;
                $itemIds = [];

                foreach (array_values($groupData['items']) as $i => $itemData) {
                    $item = ! empty($itemData['id']) ? $group->items()->find($itemData['id']) : $group->items()->make();
                    $this->ensure($item !== null, "groups.$g.items.$i.id", 'Item does not belong to this group.');
                    $product = $products->get($itemData['product_id']);
                    $this->ensure($product !== null, "groups.$g.items.$i.product_id", 'Product does not belong to this subdivision.');
                    $changedProduct = ! $item->exists || (int) $item->product_id !== (int) $product->id;
                    $gross = intdiv($this->scaled($itemData['price'], 2) * $this->scaled($itemData['quantity'], 3) + 500, 1000);
                    $subtotal += $gross;
                    $this->ensure($subtotal <= 999999999999, 'groups', 'Outlay total exceeds the supported amount.');
                    $item->fill([
                        'product_id' => $product->id,
                        'product_title_snapshot' => $changedProduct ? $product->title : $item->product_title_snapshot,
                        'is_rent' => $changedProduct ? (bool) $product->is_rent : $item->is_rent,
                        'price' => $itemData['price'], 'quantity' => $itemData['quantity'],
                        'description' => array_key_exists('description', $itemData) ? $itemData['description']
                            : ($changedProduct ? $product->description?->description : $item->description),
                        'position' => $i, 'total_cost' => $this->money($gross),
                    ]);
                    $item->save();
                    $itemIds[] = $item->id;
                    if (array_key_exists('images', $itemData) || $changedProduct) {
                        $paths = $itemData['images'] ?? ($product->image ? [$product->image] : []);
                        $item->images()->delete();
                        foreach (array_values($paths) as $position => $path) {
                            $item->images()->create(['path' => $path, 'position' => $position]);
                        }
                    }
                    $rows[] = [$item, $gross];
                }
                $group->items()->whereNotIn('id', $itemIds)->delete();
            }
            $outlay->groups()->whereNotIn('id', $groupIds)->delete();

            $percent = $this->scaled($data['percent_discount'] ?? 0, 2);
            $discount = isset($data['discount_amount'])
                ? $this->scaled($data['discount_amount'], 2)
                : intdiv($subtotal * $percent + 5000, 10000);
            $this->ensure($discount <= $subtotal, 'discount_amount', 'Discount exceeds the subtotal.');

            // Allocate rounding differences cumulatively so line totals equal the outlay total.
            $cumulative = 0;
            $allocated = 0;
            foreach ($rows as [$item, $gross]) {
                $cumulative += $gross;
                $next = $subtotal ? BigInteger::of($discount)->multipliedBy($cumulative)
                    ->plus(intdiv($subtotal, 2))->quotient($subtotal)->toInt() : 0;
                $item->total_cost = $this->money($gross - ($next - $allocated));
                $item->save();
                $allocated = $next;
            }
            $outlay->discount_amount = $this->money($discount);
            $outlay->percent_discount = isset($data['discount_amount'])
                ? ($subtotal ? $this->money(intdiv($discount * 10000 + intdiv($subtotal, 2), $subtotal)) : '0.00')
                : $this->money($percent);
            $outlay->total_cost = $this->money($subtotal - $discount);

            if (! $outlay->prepayment) {
                $outlay->prepayment_amount = null;
                $outlay->prepayment_date = null;
                $outlay->prepayment_method_id = null;
                $outlay->prepayment_cash_box_id = null;
            } else {
                $prepaymentCashBox = CashBox::query()->find($outlay->prepayment_cash_box_id);
                $outlay->prepayment_method_id = $prepaymentCashBox?->payment_method_id;
                $this->ensure($outlay->prepayment_amount !== null, 'prepayment_amount',
                    'Prepayment amount is required.');
                $this->ensure($outlay->prepayment_date !== null, 'prepayment_date',
                    'Prepayment date is required.');
                $this->ensure($outlay->prepayment_method_id !== null, 'prepayment_method_id',
                    'Prepayment payment method is required.');
                $this->ensure(
                    $this->scaled($outlay->prepayment_amount, 2) <= $subtotal - $discount,
                    'prepayment_amount',
                    'Prepayment amount cannot exceed the outlay total.'
                );
            }

            if (! $outlay->is_paid) {
                $outlay->date_of_paid = null;
                $outlay->payment_method_id = null;
                $outlay->payment_cash_box_id = null;
            } else {
                $paymentCashBox = CashBox::query()->find($outlay->payment_cash_box_id);
                $outlay->payment_method_id = $paymentCashBox?->payment_method_id;
                $this->ensure($outlay->payment_method_id !== null, 'payment_cash_box_id',
                    'Payment cash box must have a payment method.');
            }

            $outlay->save();
            $this->warehouseService->syncOutlay($outlay, $request->user()?->id);
            $this->cashLedgerService->syncOutlay($outlay, $request->user()?->id);
            $this->auditService->formSaved($outlay, $auditBefore, $request->user());

            return $this->display($outlay->fresh([
                'groups.groupName', 'groups.items.images', 'rentals',
                'paymentCashBox.paymentMethod', 'prepaymentCashBox.paymentMethod',
            ]));
        });
    }

    public function delete(DeleteOutlayRequest $request, Outlay $outlay): Outlay
    {
        return DB::transaction(function () use ($request, $outlay) {
            $outlay = Outlay::query()->lockForUpdate()->findOrFail($outlay->id);
            $this->warehouseService->removeOutlay($outlay, $request->user()?->id);
            $this->cashLedgerService->removeOutlay($outlay, $request->user()?->id);
            $outlay->delete();

            return $outlay;
        });
    }

    private function ensure(bool $condition, string $field, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages([$field => $message]);
        }
    }

    public function display(Outlay $outlay): Outlay
    {
        if ($outlay->relationLoaded('groups')) {
            $outlay->loadMissing(['groups.items.stages.updatedBy', 'groups.items.comments']);
        }

        return $outlay->load([...Outlay::DISPLAY_RELATIONS, 'workNotes'])->withAssignmentIds();
    }

    public function updateItemStages(Outlay $outlay, int $itemId, array $flags, $actor): OutlayItem
    {
        return DB::transaction(function () use ($outlay, $itemId, $flags, $actor) {
            $outlay = Outlay::query()->lockForUpdate()->findOrFail($outlay->id);
            abort_unless($actor->hasSubdivisionAccess($outlay->subdivision_id), 403);
            $item = OutlayItem::query()->whereKey($itemId)
                ->whereHas('group', fn ($query) => $query->where('outlay_id', $outlay->id))->lockForUpdate()->first();
            abort_unless($item !== null, 403);
            $stage = $item->stages()->firstOrNew();
            if ($stage->applyEditedFields($flags, $actor->id)) {
                $stage->updated_by_id = $actor->id;
                $stage->save();
            }

            return $item->load(['stages.updatedBy', 'images']);
        });
    }

    public function updateItemComments(Outlay $outlay, int $itemId, array $values, $actor): OutlayItem
    {
        return DB::transaction(function () use ($outlay, $itemId, $values, $actor) {
            $outlay = Outlay::query()->lockForUpdate()->findOrFail($outlay->id);
            abort_unless($actor->hasSubdivisionAccess($outlay->subdivision_id), 403);
            $item = OutlayItem::query()->whereKey($itemId)
                ->whereHas('group', fn ($query) => $query->where('outlay_id', $outlay->id))->lockForUpdate()->first();
            abort_unless($item !== null, 403);
            $comments = $item->comments()->firstOrNew();
            if ($comments->applyEditedFields($values, $actor->id)) {
                $comments->save();
            }

            return $item->load(['comments', 'stages.updatedBy', 'images']);
        });
    }

    public function updateWorkNotes(Outlay $outlay, array $values, $actor): Outlay
    {
        return DB::transaction(function () use ($outlay, $values, $actor) {
            $outlay = Outlay::query()->lockForUpdate()->findOrFail($outlay->id);
            abort_unless($actor->hasSubdivisionAccess($outlay->subdivision_id), 403);
            $notes = $outlay->workNotes()->firstOrNew();
            if ($notes->applyEditedFields($values, $actor->id)) {
                $notes->save();
            }

            return $outlay->load('workNotes');
        });
    }

    private function syncAssignments(Outlay $outlay, array $data): void
    {
        foreach (Outlay::ASSIGNMENTS as $field => $relation) {
            if (array_key_exists($field, $data)) {
                $outlay->{$relation}()->sync($data[$field]);
            }
        }
    }

    public function updateAssignments(Outlay $outlay, array $data, $actor): Outlay
    {
        return DB::transaction(function () use ($outlay, $data, $actor) {
            $outlay = Outlay::query()->lockForUpdate()->findOrFail($outlay->id);
            abort_unless($actor->hasSubdivisionAccess($outlay->subdivision_id), 403);
            $this->syncAssignments($outlay, $data);

            return $this->display($outlay);
        });
    }

    public function updateStatus(Outlay $outlay, int $statusId, $actor): Outlay
    {
        return DB::transaction(function () use ($outlay, $statusId, $actor) {
            $outlay = Outlay::query()->lockForUpdate()->findOrFail($outlay->id);
            abort_unless($actor->hasSubdivisionAccess($outlay->subdivision_id), 403);
            $oldStatus = (int) $outlay->status_id;
            $outlay->status_id = $statusId;
            $outlay->save();
            $this->warehouseService->syncOutlay($outlay, $actor->id);
            $this->auditService->statusChanged($outlay, $oldStatus, $statusId, $actor);

            return $this->display($outlay);
        });
    }

    private function scaled($value, int $scale): int
    {
        [$whole, $fraction] = array_pad(explode('.', (string) $value, 2), 2, '');

        return (int) $whole * (10 ** $scale) + (int) str_pad($fraction, $scale, '0');
    }

    private function money(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
