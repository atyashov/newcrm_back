<?php

namespace App\Modules\Admin\Expenses\Services;

use App\Core\Filters\Filter;
use App\Core\Traits\HasSorting;
use App\Modules\Admin\CashBoxes\Services\CashLedgerService;
use App\Modules\Admin\Expenses\Models\Expense;
use App\Modules\Admin\Expenses\Requests\ExpensesRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ExpensesService
{
    use HasSorting;

    public function __construct(private readonly CashLedgerService $cashLedgerService) {}

    public function getExpenses($pageSize, Filter $filter, $sort = null, array $subdivisionIds = []): LengthAwarePaginator
    {
        $result = Expense::filter($filter)
            ->whereIn('subdivision_id', $subdivisionIds)
            ->with([
                'subdivision',
                'expenseGroup',
                'cashBox.paymentMethod',
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

    public function save(ExpensesRequest $request, Expense $expense): Expense
    {
        return DB::transaction(function () use ($request, $expense) {
            if ($expense->exists) {
                $expense = Expense::query()->lockForUpdate()->findOrFail($expense->id);
            }

            $expense->fill($request->only($expense->getFillable()));
            $expense->save();
            $this->cashLedgerService->syncExpense($expense, $request->user()?->id);

            return $expense;
        });
    }

    public function delete(Expense $expense, ?int $userId): Expense
    {
        return DB::transaction(function () use ($expense, $userId) {
            $expense = Expense::query()->lockForUpdate()->findOrFail($expense->id);
            $this->cashLedgerService->removeExpense($expense, $userId);
            $expense->delete();

            return $expense;
        });
    }
}
