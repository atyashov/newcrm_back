<?php

namespace Database\Seeders;

use App\Modules\Admin\Menu\Models\Menu;
use App\Modules\Admin\Role\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersSubdivisionsMenusSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $parent = Menu::query()->where('type', 'admin')->where('title', 'Доступы')->where('path', '#')->first();
            foreach ([
                ['users', 'Пользователи', 'USERS', 3],
                ['subdivisions', 'Подразделения', 'SUBDIVISIONS', 4],
            ] as [$path, $title, $prefix, $position]) {
                $permissionIds = [];
                foreach (['ACCESS' => 'Просмотр', 'MANAGE' => 'Управление'] as $suffix => $label) {
                    $permissionIds[] = Permission::query()->firstOrCreate(
                        ['alias' => $prefix === 'USERS' && $suffix === 'MANAGE' ? 'USER_MANAGE' : $prefix.'_'.$suffix], ['title' => $label.': '.$title]
                    )->id;
                }
                $super = Permission::query()->where('alias', 'SUPER_ADMINISTRATOR')->value('id');
                if ($super !== null) {
                    $permissionIds[] = $super;
                }
                $menu = Menu::query()->where('type', 'admin')->where('path', $path)->first();
                if (! $menu) {
                    $menu = new Menu;
                    $menu->forceFill([
                        'title' => $title, 'path' => $path, 'type' => 'admin',
                        'parent' => $parent?->id ?? 0, 'sort_order' => $position,
                    ])->save();
                }
                $menu->perms()->syncWithoutDetaching($permissionIds);
                if ($menu->parent) {
                    Menu::find($menu->parent)?->perms()->syncWithoutDetaching($permissionIds);
                }
            }
        });
    }
}
