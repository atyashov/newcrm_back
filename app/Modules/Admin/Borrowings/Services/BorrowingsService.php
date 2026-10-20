<?php

namespace App\Modules\Admin\Borrowings\Services;

use App\Core\Filters\Filter;
use App\Core\Traits\HasSorting;
use App\Modules\Admin\Borrowings\Models\Borrowing;
use App\Modules\Admin\Borrowings\Requests\BorrowingsRequest;
use App\Modules\Admin\CashBoxes\Services\CashLedgerService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BorrowingsService
{
    use HasSorting;

    public function __construct(private readonly CashLedgerService $cashLedgerService) {}

    public function getBorrowings($pageSize, Filter $filter, $sort = null, array $subdivisionIds = []): LengthAwarePaginator
    {
        $result = Borrowing::filter($filter)
            ->whereIn('subdivision_sender_id', $subdivisionIds)
            ->whereIn('subdivision_receiver_id', $subdivisionIds)
            ->with([
                'subdivisionSender',
                'subdivisionReceiver',
                'cashBoxSender.paymentMethod',
                'cashBoxReceiver.paymentMethod',
            ]);

        if ($sort) {
            $result = $this->applySorting($result, $sort);
        } else {
            $result
                ->orderByDesc('date')
                ->orderByDesc('id');
        }

        return $result->paginate($pageSize);
    }

    public function save(BorrowingsRequest $request, Borrowing $borrowing): Borrowing
    {
        return DB::transaction(function () use ($request, $borrowing) {
            if ($borrowing->exists) {
                $borrowing = Borrowing::query()->lockForUpdate()->findOrFail($borrowing->id);
            }

            $borrowing->fill($request->only($borrowing->getFillable()));
            $borrowing->save();
            $this->cashLedgerService->syncBorrowing($borrowing, $request->user()?->id);

            return $borrowing;
        });
    }

    public function delete(Borrowing $borrowing, ?int $userId): Borrowing
    {
        return DB::transaction(function () use ($borrowing, $userId) {
            $borrowing = Borrowing::query()->lockForUpdate()->findOrFail($borrowing->id);
            $this->cashLedgerService->removeBorrowing($borrowing, $userId);
            $borrowing->delete();

            return $borrowing;
        });
    }
}
