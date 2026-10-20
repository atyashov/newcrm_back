<?php

namespace Database\Seeders;

use App\Modules\Admin\Menu\Models\Menu;
use App\Modules\Admin\Role\Models\Permission;
use Illuminate\Database\Seeder;

class MenuPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminPerm = Permission::query()
            ->where('alias', 'SUPER_ADMINISTRATOR')
            ->first();

        $menus = Menu::get()->pluck('id')->toArray();

        $superAdminPerm->menus()->syncWithoutDetaching($menus);

    }
}
