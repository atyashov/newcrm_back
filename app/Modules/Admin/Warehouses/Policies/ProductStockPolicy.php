<?php

namespace App\Modules\Admin\Warehouses\Policies;

use App\Modules\Admin\User\Models\User;

class ProductStockPolicy
{
    public function view(User $user): bool
    {
        return $user->canDo(['SUPER_ADMINISTRATOR', 'WAREHOUSE_ACCESS']);
    }

    public function manage(User $user): bool
    {
        return $user->canDo(['SUPER_ADMINISTRATOR', 'WAREHOUSE_MANAGE']);
    }
}
