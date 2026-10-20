<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrationDeploy extends Command
{
    public const MIGRATION_PATHS = [
        'database/migrations',
        'app/Modules/Admin/User/Migrations',
        'app/Modules/Admin/Role/Migrations',
        'app/Modules/Admin/ClientTypes/Migrations',
        'app/Modules/Admin/Clients/Migrations',
        'app/Modules/Admin/Menu/Migrations',
        'app/Modules/Admin/Subdivisions/Migrations',
        'app/Modules/Admin/OutlayGroupNames/Migrations',
        'app/Modules/Admin/PaymentMethods/Migrations',
        'app/Modules/Admin/CashBoxes/Migrations',
        'app/Modules/Admin/Stores/Migrations',
        'app/Modules/Admin/Products/Migrations',
        'app/Modules/Admin/Sources/Migrations',
        'app/Modules/Admin/Statuses/Migrations',
        'app/Modules/Admin/Subjects/Migrations',
        'app/Modules/Admin/Outlays/Migrations',
        'app/Modules/Admin/OutlayGroups/Migrations',
        'app/Modules/Admin/Warehouses/Migrations',
        'app/Modules/Admin/ExpenseGroups/Migrations',
        'app/Modules/Admin/Expenses/Migrations',
        'app/Modules/Admin/Borrowings/Migrations',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migration-deploy
        {--revert= : Roll back N migrations, or all}
        {--seed : Run DatabaseSeeder after successful migrations}
        {--force : Allow execution in production}
        {--pretend : Show migration SQL without executing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run application and module migrations; optionally seed required data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $options = ['--path' => self::MIGRATION_PATHS, '--force' => $this->option('force'), '--pretend' => $this->option('pretend')];
        $revert = $this->option('revert');
        if ($revert !== null) {
            if ($this->option('seed') || ($revert !== 'all' && (! ctype_digit($revert) || (int) $revert < 1))) {
                $this->error('Use --revert=all or --revert=N (N >= 1), without --seed.');

                return self::FAILURE;
            }

            return $this->call($revert === 'all' ? 'migrate:reset' : 'migrate:rollback',
                $revert === 'all' ? $options : [...$options, '--step' => (int) $revert]);
        }

        if ($this->option('seed') && $this->option('pretend')) {
            $this->error('--seed cannot be combined with --pretend.');

            return self::FAILURE;
        }

        return $this->call('migrate', [...$options, '--seed' => $this->option('seed')]);
    }
}
