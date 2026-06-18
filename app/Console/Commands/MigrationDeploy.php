<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MigrationDeploy extends Command
{

    private $migrationPaths = [
        'app/Modules/Admin/User/Migrations',
        'app/Modules/Admin/Role/Migrations',
        'app/Modules/Admin/ClientTypes/Migrations',
        'app/Modules/Admin/Clients/Migrations',
        'app/Modules/Admin/Menu/Migrations',
        'app/Modules/Admin/Subdivisions/Migrations',
        'app/Modules/Admin/OutlayGroups/Migrations',
        'app/Modules/Admin/PaymentMethods/Migrations',
        'Modules/Admin/Products/Migrations/2023_01_07_131006_create_category_product_table',
        'Modules/Admin/Products/Migrations/2023_01_07_131006_create_categories_table',
        'Modules/Admin/Products/Migrations/2023_01_07_131006_create_products_table',
        'app/Modules/Admin/Products/Migrations',
        'app/Modules/Admin/Sources/Migrations',
        'app/Modules/Admin/Statuses/Migrations',
        'app/Modules/Admin/Subjects/Migrations',
        'app/Modules/Admin/Outlays/Migrations',

    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migration-deploy {--revert}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Запуск всех миграций из модулей. --revert=all откатит все миграции.
        revert=* откатит указанное кол-во миграций';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($revert = $this->option('revert')) {
            $revertCount = ($revert == "all") ? count($this->migrationPaths) : $revert;
            for ($i = 0; $i < $revertCount; $i++) {
                $this->call('migrate:rollback', ['--path' => array_reverse($this->migrationPaths)[$i]]);
            }
        } else {
            foreach ($this->migrationPaths as $path) {
                $this->call('migrate', ['--path' => $path]);
            }
        }

    }
}
