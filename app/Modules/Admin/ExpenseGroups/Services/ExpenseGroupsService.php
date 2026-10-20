<?php

namespace App\Modules\Admin\ExpenseGroups\Services;

use App\Modules\Admin\ExpenseGroups\Models\ExpenseGroup;
use App\Modules\Admin\ExpenseGroups\Requests\ExpenseGroupsRequest;
use Illuminate\Database\Eloquent\Collection;

class ExpenseGroupsService
{
    public function getExpenseGroups(): Collection
    {
        return ExpenseGroup::query()
            ->with([
                'children' => function ($query) {
                    $query->orderBy('title');
                },
            ])
            ->whereNull('parent_id')
            ->orderBy('title')
            ->get();
    }

    public function save(ExpenseGroupsRequest $request, ExpenseGroup $expenseGroup): ExpenseGroup
    {
        $expenseGroup->fill($request->only($expenseGroup->getFillable()));
        $expenseGroup->save();

        return $expenseGroup;
    }
}
