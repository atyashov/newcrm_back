<?php

namespace Database\Seeders;

use App\Modules\Admin\Role\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'SUPER_ADMINISTRATOR' => 'SUPER ADMINISTRATOR',
            'ROLES_ACCESS' => 'Просмотр ролей',
            'ROLES_MANAGE' => 'Управление ролями',
            'USERS_ACCESS' => 'Просмотр пользователей',
            'USERS_MANAGE' => 'Управление пользователями',
            'USER_MANAGE' => 'Создание и управление пользователями',
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
            'MENU_ACCESS' => 'Доступ к меню',
            'MENU_MANAGE' => 'Управление меню',
            'EXPENSE_GROUP_ACCESS' => 'Доступ к группам расходов',
            'EXPENSE_GROUP_MANAGE' => 'Управление группой расходов',
            'EXPENSE_ACCESS' => 'Доступ к расходам',
            'EXPENSE_MANAGE' => 'Управление расходами',
            'CAASHBOX_ACCESS' => 'Доступ к кассам',
            'CASHBOX_MANAGE' => 'Управление кассами',
            'BORROWING_ACCESS' => 'Доступ к займам',
            'BORROWING_MANAGE' => 'Управление займами',
            'WAREHOUSE_ACCESS' => 'Доступ к складу',
            'WAREHOUSE_MANAGE' => 'Управление складом',
            'STORE_ACCESS' => 'Просмотр складов хранения',
            'STORE_MANAGE' => 'Управление складами хранения',
        ];

        foreach ($permissions as $permission => $title) {
            Permission::firstOrCreate([
                'alias' => $permission,
            ], ['title' => $title]);
        }
    }
}
