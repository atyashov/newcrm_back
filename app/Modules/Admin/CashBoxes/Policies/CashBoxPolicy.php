<?php

namespace App\Modules\Admin\CashBoxes\Policies;

use App\Modules\Admin\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CashBoxPolicy
{
    use HandlesAuthorization;

    public function view(User $user): bool
    {
        return $user->canDo(['SUPER_ADMINISTRATOR', 'CAASHBOX_ACCESS']);
    }

    public function manage(User $user): bool
    {
        return $user->canDo(['SUPER_ADMINISTRATOR', 'CASHBOX_MANAGE']);
    }
}
