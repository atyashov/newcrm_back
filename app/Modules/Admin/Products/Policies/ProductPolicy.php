<?php

namespace App\Modules\Admin\Products\Policies;

use App\Modules\Admin\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function view(User $user){
        return $user->canDo(['SUPER_ADMINISTRATOR', 'PRODUCTS_ACCESS']);
    }

    public function create(User $user){
        return $user->canDo(['SUPER_ADMINISTRATOR', 'PRODUCTS_MANAGE']);
    }
}
