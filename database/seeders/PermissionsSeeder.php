<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Modules\Admin\Role\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'ROLES_ACCESS' => 'Просмотр ролей',
            'ROLES_MANAGE' => 'Управление ролями',
            'USERS_ACCESS' => 'Просмотр пользователей',
            'USERS_MANAGE' => 'Управление пользователями',
            'CLIENTS_ACCESS' => 'Просмотр клиентов',
            'CLIENTS_MANAGE' => 'Управление клиентами',
            'ORDERS_ACCESS' => 'Просмотр заказов',
            'ORDERS_MANAGE' => 'Управление заказами',
            'PRODUCTS_ACCESS' => 'Просмотр продуктов',
            'PRODUCTS_MANAGE' => 'Управление продуктами',
            'OUTLAYS_ACCESS' => 'Просмотр смет',
            'OUTLAYS_MANAGE' => 'Управление сметами',
            'CLIENT_TYPES_ACCESS' => 'Просмотр типов клиентов',
            'CLIENT_TYPES_MANAGE' => 'Управление типами клиентов',
            'DASHBOARD_ACCESS' => 'Просмотр панели управления',
            'SUBDIVISIONS_ACCESS' => 'Просмотр подразделений',
            'SUBDIVISIONS_MANAGE' => 'Управление подразделениями',
            'STATUSES_ACCESS' => 'Просмотр статусов',
            'STATUSES_MANAGE' => 'Управление статусами',
            'TASKS_ACCESS' => 'Просмотр задач',
            'TASKS_MANAGE' => 'Управление задачами',
            'MENU_ACCESS' => "Доступ к меню",
            'MENU_MANAGE' => "Управление меню",
        ];

        Permission::query()->delete();

        foreach ($permissions as $permission => $title) {
            Permission::create([
                'alias' => $permission,
                'title' => $title,
            ]);
        }
    }
}
