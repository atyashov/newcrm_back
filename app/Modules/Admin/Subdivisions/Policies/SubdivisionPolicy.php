<?php

namespace App\Modules\Admin\Subdivisions\Policies;

use App\Modules\Admin\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubdivisionPolicy
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
        return $user->canDo(['SUPER_ADMINISTRATOR', 'SUBDIVISIONS_ACCESS']);
    }

    public function delete(User $user){
        return $this->manage($user);
    }

    public function manage(User $user): bool
    {
        return $user->canDo(['SUPER_ADMINISTRATOR', 'SUBDIVISIONS_MANAGE']);
    }
}
