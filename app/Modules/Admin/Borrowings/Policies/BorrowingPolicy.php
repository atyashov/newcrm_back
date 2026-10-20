<?php

namespace App\Modules\Admin\Borrowings\Policies;

use App\Modules\Admin\Borrowings\Models\Borrowing;
use App\Modules\Admin\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BorrowingPolicy
{
    use HandlesAuthorization;

    public function view(User $user, ?Borrowing $borrowing = null): bool
    {
        return $user->canDo(['SUPER_ADMINISTRATOR', 'BORROWING_ACCESS'])
            && $this->hasAccess($user, $borrowing);
    }

    public function manage(User $user, ?Borrowing $borrowing = null): bool
    {
        return $user->canDo(['SUPER_ADMINISTRATOR', 'BORROWING_MANAGE'])
            && $this->hasAccess($user, $borrowing);
    }

    private function hasAccess(User $user, ?Borrowing $borrowing): bool
    {
        return ! $borrowing?->exists
            || ($user->hasSubdivisionAccess($borrowing->subdivision_sender_id)
                && $user->hasSubdivisionAccess($borrowing->subdivision_receiver_id));
    }
}
