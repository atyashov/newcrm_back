<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Modules\Admin\Menu\Models\Menu;
use Illuminate\Support\Facades\DB;

class MenusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menus = [
            'dashboard' => [
                'title' => 'Панель управления',
                'path' => 'dashboard',
                'type' => 'admin',
                'sort_order' => 1,
            ],
            'clients' => [
                'title' => 'Контрагенты',
                'path' => '#',
                'type' => 'admin',
                'sort_order' => 2,
                'childs' => [
                    'individuals' => [
                        'title' => 'Физические лица',
                        'path' => 'individuals',
                        'type' => 'admin',
                        'sort_order' => 1,
                    ],
                    'legal_entities' => [
                        'title' => "Юридические лица",
                        'path' => 'legal-entity',
                        'type' => 'admin',
                        'sort_order' => 2,
                    ],
                ],
            ],
            'nomenclatures' => [
                'title' => 'Номенклатура',
                'path' => '#',
                'type' => 'admin',
                'sort_order' => 3,
                'childs' => [
                    'products' => [
                        'title' => 'Товары',
                        'path' => 'products',
                        'type' => 'admin',
                        'sort_order' => 1,
                    ],
                    'product-groups' => [
                        'title' => 'Группы товаров',
                        'path' => 'product-groups',
                        'type' => 'admin',
                        'sort_order' => 2,
                    ],
                ],
            ],
            'analitics' => [
                'title' => 'Аналитика',
                'path' => '#',
                'type' => 'admin',
                'sort_order' => 4,
                'childs' => [
                    'managers' => [
                        'title' => 'Менеджеры',
                        'path' => 'managers-analitycs',
                        'type' => 'admin',
                        'sort_order' => 1,
                    ],
                    'costs' => [
                        'title' => 'Расходы',
                        'path' => 'costs-analitycs',
                        'type' => 'admin',
                        'sort_order' => 2,
                    ],
                    'products' => [
                        'title' => 'Товары',
                        'path' => 'products-analitycs',
                        'type' => 'admin',
                        'sort_order' => 2,
                    ],
                    'categories' => [
                        'title' => 'Категории',
                        'path' => 'categories-analitycs',
                        'type' => 'admin',
                        'sort_order' => 3,
                    ],
                    'storage' => [
                        'title' => 'Склад',
                        'path' => 'storage-analitycs',
                        'type' => 'admin',
                        'sort_order' => 4,
                    ],
                ]
            ],
            'directories' => [
                'title' => 'Справочники',
                'path' => '#',
                'type' => 'admin',
                'sort_order' => 5,
                'childs' => [
                    'storages' => [
                        'title' => 'Склады',
                        'path' => 'storages',
                        'type' => 'admin',
                        'sort_order' => 1,
                    ],
                    'client-sources' => [
                        'title' => 'Источники клиентов',
                        'path' => 'client-sources',
                        'type' => 'admin',
                        'sort_order' => 2,
                    ],
                    'payment-methods' => [
                        'title' => 'Методы оплаты',
                        'path' => 'payment-methods',
                        'type' => 'admin',
                        'sort_order' => 3,
                    ],
                    'order-subjects' => [
                        'title' => 'Тематики заказов',
                        'path' => 'order-subjects',
                        'type' => 'admin',
                        'sort_order' => 4,
                    ],
                    'outlay-statuses' => [
                        'title' => 'Статусы смет',
                        'path' => 'outlay-statuses',
                        'type' => 'admin',
                        'sort_order' => 5,
                    ],
                    'outlay-groups' => [
                        'title' => 'Группы в сметах',
                        'path' => 'outlay-groups',
                        'type' => 'admin',
                        'sort_order' => 6,
                    ],
                    'consumption-groups' => [
                        'title' => 'Группы расходов',
                        'path' => 'consumption-groups',
                        'type' => 'admin',
                        'sort_order' => 7,
                    ],
                    'partner-types' => [
                        'title' => 'Типы контрагентов',
                        'path' => 'partner-types',
                        'type' => 'admin',
                        'sort_order' => 8,
                    ],
                    'documents' => [
                        'title' => 'Документы',
                        'path' => 'documents',
                        'type' => 'admin',
                        'sort_order' => 9,
                    ],
                    'outlay-group-templates' => [
                        'title' => 'Шаблоны групп смет',
                        'path' => 'outlay-group-templates',
                        'type' => 'admin',
                        'sort_order' => 10,
                    ],
                    'outlay-cheque-statuses' => [
                        'title' => 'Статусы чеков смет',
                        'path' => 'outlay-cheque-statuses',
                        'type' => 'admin',
                        'sort_order' => 11,
                    ],
                ],
            ],
            'accesses' => [
                'title' => 'Доступы',
                'path' => '#',
                'type' => 'admin',
                'sort_order' => 6,
                'childs' => [
                    'roles' => [
                        'title' => 'Роли',
                        'path' => 'roles',
                        'type' => 'admin',
                        'sort_order' => 1,
                    ],
                    'menus' => [
                        'title' => 'Меню',
                        'path' => 'menu-access',
                        'type' => 'admin',
                        'sort_order' => 2,
                    ],
                ],
            ],
            'cashes' => [
                'title' => 'Кассы',
                'path' => 'cashes',
                'type' => 'admin',
                'sort_order' => 6,
            ],
            'tasks' => [
                'title' => 'Задачи',
                'path' => 'tasks',
                'type' => 'admin',
                'sort_order' => 7,
            ],
            'my-tasks' => [
                'title' => 'Мои задачи',
                'path' => 'my-tasks',
                'type' => 'admin',
                'sort_order' => 8,
            ],
            'my-outlays' => [
                'title' => 'Мои сметы',
                'path' => 'my-outlays',
                'type' => 'admin',
                'sort_order' => 9,
            ],
            'outlay-logs' => [
                'title' => 'Логи смет',
                'path' => 'outlay-logs',
                'type' => 'admin',
                'sort_order' => 10,
            ],
        ];

        Menu::query()->delete();
        DB::statement('ALTER TABLE `menus` AUTO_INCREMENT = 1;');

        foreach ($menus as $menu) {
            $menu['parent'] = 0;

            $childs = $menu['childs'] ?? [];
            unset($menu['childs']);
            $menuModel = Menu::create($menu);
            foreach ($childs as $child) {
                $child['parent'] = $menuModel->id;
                $childModel = Menu::create($child);
            }
        }
    }
}
