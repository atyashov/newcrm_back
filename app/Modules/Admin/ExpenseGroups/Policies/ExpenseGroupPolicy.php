<?php

namespace App\Modules\Admin\ExpenseGroups\Policies;

use App\Modules\Admin\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExpenseGroupPolicy
{
    use HandlesAuthorization;

    public function view(User $user): bool
    {
        return $user->canDo(['SUPER_ADMINISTRATOR', 'EXPENSE_GROUP_ACCESS']);
    }

    public function manage(User $user): bool
    {
        return $user->canDo(['SUPER_ADMINISTRATOR', 'EXPENSE_GROUP_MANAGE']);
    }
}
