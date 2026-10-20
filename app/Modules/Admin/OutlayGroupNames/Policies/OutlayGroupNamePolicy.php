<?php

namespace App\Modules\Admin\OutlayGroupNames\Policies;

use App\Modules\Admin\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OutlayGroupNamePolicy
{
    use HandlesAuthorization;

    public function view(User $user): bool
    {
        return $user->canDo(['SUPER_ADMINISTRATOR', 'OUTLAYS_ACCESS']);
    }

    public function manage(User $user): bool
    {
        return $user->canDo(['SUPER_ADMINISTRATOR', 'OUTLAYS_MANAGE']);
    }
}
