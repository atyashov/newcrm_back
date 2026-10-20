<?php

namespace Tests\Feature;

use App\Console\Commands\MigrationDeploy;
use Database\Seeders\CreateAdminUser;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DeploymentSetupTest extends TestCase
{
    private array $originalAdminEnvironment = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (['CRM_ADMIN_LOGIN' => 'admin', 'CRM_ADMIN_EMAIL' => 'bootstrap@example.test', 'CRM_ADMIN_PASSWORD' => 'test-bootstrap-password'] as $key => $value) {
            $this->originalAdminEnvironment[$key] = Env::getRepository()->get($key);
            Env::getRepository()->set($key, $value);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->originalAdminEnvironment as $key => $value) {
            if ($value === null) {
                Env::getRepository()->clear($key);
            } else {
                Env::getRepository()->set($key, $value);
            }
        }
        parent::tearDown();
    }

    public function test_all_migration_files_are_covered_and_fresh_deployment_and_seeding_are_repeatable(): void
    {
        $files = app('files');
        $all = collect($files->allFiles(app_path()))->filter(fn ($file) => str_contains(str_replace('\\', '/', $file->getPathname()), '/Migrations/'))->map(fn ($file) => $file->getRealPath())
            ->merge(collect($files->allFiles(database_path('migrations')))->map(fn ($file) => $file->getRealPath()))->sort()->values()->all();
        $covered = collect(MigrationDeploy::MIGRATION_PATHS)->flatMap(fn ($path) => collect($files->files(base_path($path)))->map(fn ($file) => $file->getRealPath()))->sort()->values()->all();
        $this->assertSame($all, $covered);
        $this->artisan('app:migration-deploy', ['--force' => true, '--seed' => true])->assertSuccessful();
        $this->assertDatabaseCount('migrations', count($all));
        $this->assertTrue(Schema::hasTable('oauth_access_tokens'));
        $this->assertTrue(Schema::hasTable('outlay_audit_events'));
        $this->assertTrue(Schema::hasTable('outlay_work_notes'));
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['login' => 'admin', 'email' => 'bootstrap@example.test']);
        $this->assertTrue(Hash::check('test-bootstrap-password', DB::table('users')->where('login', 'admin')->value('password')));
        $this->assertDatabaseCount('individuals', 0);
        $this->assertDatabaseCount('client_types', 1);
        $counts = collect(['users', 'roles', 'role_user', 'role_permissions', 'menus', 'permissions', 'permission_menu', 'subdivisions', 'cash_boxes', 'payment_methods', 'client_types'])
            ->mapWithKeys(fn ($table) => [$table => DB::table($table)->count()])->all();
        DB::table('subdivisions')->where('id', 1)->update(['description' => 'Custom description', 'logo' => 'custom.png']);
        DB::table('menus')->where('path', 'users')->update(['title' => 'Custom users']);
        DB::table('permissions')->where('alias', 'USER_MANAGE')->update(['title' => 'Custom permission']);
        DB::table('cash_boxes')->where('id', 1)->update(['balance' => 125, 'opening_balance' => 100]);
        $this->artisan('app:migration-deploy', ['--force' => true, '--seed' => true])->assertSuccessful();
        foreach ($counts as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
        $this->assertDatabaseHas('subdivisions', ['id' => 1, 'description' => 'Custom description', 'logo' => 'custom.png']);
        $this->assertDatabaseHas('menus', ['path' => 'users', 'title' => 'Custom users']);
        $this->assertDatabaseHas('permissions', ['alias' => 'USER_MANAGE', 'title' => 'Custom permission']);
        $this->assertDatabaseHas('cash_boxes', ['id' => 1, 'balance' => 125, 'opening_balance' => 100]);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_all_migrations_can_be_rolled_back_and_deployed_again(): void
    {
        $this->artisan('app:migration-deploy', ['--force' => true])->assertSuccessful();
        $this->artisan('app:migration-deploy', ['--force' => true, '--revert' => 'all'])->assertSuccessful();
        $this->assertDatabaseCount('migrations', 0);
        $this->assertFalse(Schema::hasTable('users'));
        $this->assertFalse(Schema::hasTable('role_user'));
        $this->assertFalse(Schema::hasTable('permission_menu'));
        $this->artisan('app:migration-deploy', ['--force' => true])->assertSuccessful();
    }

    public function test_invalid_deploy_options_are_rejected_before_migration(): void
    {
        $this->artisan('app:migration-deploy', ['--revert' => '0'])->assertFailed();
        $this->artisan('app:migration-deploy', ['--seed' => true, '--revert' => 'all'])->assertFailed();
        $this->artisan('app:migration-deploy', ['--seed' => true, '--pretend' => true])->assertFailed();
        $this->assertFalse(Schema::hasTable('users'));
    }

    public function test_explicit_admin_seeder_preserves_existing_user_password_and_role_bindings(): void
    {
        $this->artisan('app:migration-deploy', ['--force' => true])->assertSuccessful();
        $hash = Hash::make('existing-password');
        $id = DB::table('users')->insertGetId(['login' => 'admin', 'email' => 'existing@example.com', 'password' => $hash, 'status' => '1']);
        Env::getRepository()->clear('CRM_ADMIN_EMAIL');
        Env::getRepository()->clear('CRM_ADMIN_PASSWORD');
        $this->seed(DatabaseSeeder::class);
        $this->seed(CreateAdminUser::class);
        $this->seed(CreateAdminUser::class);
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseHas('users', ['id' => $id, 'password' => $hash, 'email' => 'existing@example.com']);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('roles', 1);
        $this->assertDatabaseCount('role_user', 1);
        $this->assertDatabaseCount('role_permissions', 1);
    }

    public function test_general_seeding_binds_only_admin_to_the_super_role_found_by_alias(): void
    {
        $this->artisan('app:migration-deploy', ['--force' => true])->assertSuccessful();
        $otherId = DB::table('users')->insertGetId(['login' => 'ordinary', 'firstname' => 'admin', 'email' => 'other@example.test', 'password' => Hash::make('other-password'), 'status' => '1']);
        DB::table('roles')->insert([
            ['id' => 20, 'alias' => 'BASIC', 'title' => 'Basic'],
            ['id' => 50, 'alias' => 'SUPER_ADMINISTRATOR', 'title' => 'Existing super role'],
        ]);
        DB::table('permissions')->insert(['id' => 20, 'alias' => 'CUSTOM', 'title' => 'Custom']);
        DB::table('role_user')->insert(['role_id' => 20, 'user_id' => $otherId]);
        DB::table('role_permissions')->insert([
            ['role_id' => 20, 'permission_id' => 20], ['role_id' => 50, 'permission_id' => 20],
        ]);
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);
        $adminId = DB::table('users')->where('login', 'admin')->value('id');
        $superPermission = DB::table('permissions')->where('alias', 'SUPER_ADMINISTRATOR')->value('id');
        $this->assertNotSame($otherId, $adminId);
        $this->assertDatabaseHas('role_user', ['role_id' => 50, 'user_id' => $adminId]);
        $this->assertDatabaseMissing('role_user', ['role_id' => 50, 'user_id' => $otherId]);
        $this->assertDatabaseHas('role_user', ['role_id' => 20, 'user_id' => $otherId]);
        $this->assertDatabaseHas('role_permissions', ['role_id' => 50, 'permission_id' => $superPermission]);
        $this->assertDatabaseMissing('role_permissions', ['role_id' => 20, 'permission_id' => $superPermission]);
        $this->assertDatabaseHas('role_permissions', ['role_id' => 50, 'permission_id' => 20]);
        $this->assertDatabaseHas('roles', ['id' => 50, 'title' => 'Existing super role']);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('roles', 2);
        $this->assertDatabaseCount('role_user', 2);
        $this->assertDatabaseCount('role_permissions', 3);
    }

    public function test_new_admin_without_bootstrap_credentials_rolls_back_general_seeding(): void
    {
        $this->artisan('app:migration-deploy', ['--force' => true])->assertSuccessful();
        Env::getRepository()->clear('CRM_ADMIN_EMAIL');
        Env::getRepository()->clear('CRM_ADMIN_PASSWORD');
        try {
            $this->seed(DatabaseSeeder::class);
            $this->fail('An administrator without credentials was created.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('CRM_ADMIN_PASSWORD', $exception->getMessage());
        }
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('roles', 0);
        $this->assertDatabaseCount('permissions', 0);
    }
}
