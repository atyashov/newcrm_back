<?php

namespace App\Modules\Admin\Expenses\Policies;

use App\Modules\Admin\Expenses\Models\Expense;
use App\Modules\Admin\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExpensePolicy
{
    use HandlesAuthorization;

    public function view(User $user, ?Expense $expense = null): bool
    {
        return $user->canDo(['SUPER_ADMINISTRATOR', 'EXPENSE_ACCESS'])
            && (! $expense?->exists || $user->hasSubdivisionAccess($expense->subdivision_id));
    }

    public function manage(User $user, ?Expense $expense = null): bool
    {
        return $user->canDo(['SUPER_ADMINISTRATOR', 'EXPENSE_MANAGE'])
            && (! $expense?->exists || $user->hasSubdivisionAccess($expense->subdivision_id));
    }
}
