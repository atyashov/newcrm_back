<?php

namespace App\Modules\Admin\ClientTypes\Policies;

use \App\Modules\Admin\User\Models\User;
use Illuminate\Support\Facades\Auth;

class ClientTypePolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function view(User $user){
        return $user->canDo(['SUPER_ADMINISTRATOR', 'CLIENT_TYPES_ACCESS']);
    }
}
