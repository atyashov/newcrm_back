<?php

namespace App\Modules\Admin\Role\Policies;

use \App\Modules\Admin\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PermissionPolicy
{
    use HandlesAuthorization;
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function view(User $user){
        return $user->canDo(['SUPER_ADMINISTRATOR', 'ROLES_ACCESS']);
    }
}
