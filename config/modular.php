<?php
return [
    'path' => base_path() . '/app/Modules',
    'base_namespace' => 'App\Modules',
    'groupWithoutPrefix' => 'Pub',

    'groupMiddleware' => [
        'Admin' => [
            'web' => ['auth'],
            'api' => ['auth:api', 'users.subdivisions'],
        ]
    ],

    'modules' => [
        'Admin' => [
            'Stores',
            'OutlayGroupNames',
            'Borrowings',
            'Expenses',
            'Incomes',
            'ExpenseGroups',
            'CashBoxes',
            'Clients',
            'ClientTypes',
            'Dashboard',
            'Menu',
            'OutlayGroups',
            'Outlays',
            'PaymentMethods',
            'Products',
            'Role',
            'Sources',
            'Statuses',
            'Subdivisions',
            'Subjects',
            'User',
            'Warehouses',
            'File'
        ],

        'Pub' => [
            'Auth',
            'Outlays',
        ],
    ]
];
