<?php

namespace App\Modules\Admin\Stores\Policies;

use App\Modules\Admin\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StorePolicy
{
    use HandlesAuthorization;

    public function view(User $user): bool
    {
        return $user->canDo(['SUPER_ADMINISTRATOR', 'STORE_ACCESS']);
    }

    public function manage(User $user): bool
    {
        return $user->canDo(['SUPER_ADMINISTRATOR', 'STORE_MANAGE']);
    }
}
