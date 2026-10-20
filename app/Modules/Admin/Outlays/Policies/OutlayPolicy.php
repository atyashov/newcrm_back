<?php

namespace App\Modules\Admin\Outlays\Policies;

use App\Modules\Admin\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OutlayPolicy
{
    use HandlesAuthorization;

    public function view(User $user, ?\App\Modules\Admin\Outlays\Models\Outlay $outlay = null): bool
    {
        return $user->canDo(['SUPER_ADMINISTRATOR', 'OUTLAYS_ACCESS'])
            && (! $outlay?->exists || $user->hasSubdivisionAccess($outlay->subdivision_id));
    }

    public function manage(User $user, ?\App\Modules\Admin\Outlays\Models\Outlay $outlay = null): bool
    {
        return $user->canDo(['SUPER_ADMINISTRATOR', 'OUTLAYS_MANAGE'])
            && (! $outlay?->exists || $user->hasSubdivisionAccess($outlay->subdivision_id));
    }
}
