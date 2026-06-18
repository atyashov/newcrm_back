<?php

namespace App\Modules\Admin\Menu\Policies;

use \App\Modules\Admin\User\Models\User;

class MenuPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }
    public function view(User $user){
        return $user->canDo(['SUPER_ADMINISTRATOR', 'MENU_ACCESS']);
    }

    public function manage(User $user){
        return $user->canDo(['SUPER_ADMINISTRATOR', 'MENU_MANAGE']);
    }
}
