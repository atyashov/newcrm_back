<?php

namespace Tests\Feature;

use App\Modules\Admin\User\Models\User;
use App\Modules\Pub\Auth\Controllers\Api\LoginController;
use App\Modules\Pub\Auth\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach ([
            'User/Migrations/2022_10_05_104826_create_users_table.php',
            'Role/Migrations/2022_11_11_132905_create_roles_table.php',
            'Role/Migrations/2022_11_13_065817_create_permissions.php',
            'Role/Migrations/2022_11_13_070235_create_role_permissions.php',
            'Subdivisions/Migrations/2022_12_05_174648_create_subdivisions_table.php',
            'Subdivisions/Migrations/2026_03_22_045705_create_users_subdivisions_table.php',
        ] as $path) {
            (require app_path('Modules/Admin/'.$path))->up();
        }
        DB::table('roles')->insert(['id' => 1, 'alias' => 'staff', 'title' => 'Staff']);
        foreach ([
            '2016_06_01_000002_create_oauth_access_tokens_table.php',
            '2016_06_01_000003_create_oauth_refresh_tokens_table.php',
            '2016_06_01_000004_create_oauth_clients_table.php',
        ] as $path) {
            (require base_path('vendor/laravel/passport/database/migrations/'.$path))->up();
        }
    }

    private function signIn(array $permissions): void
    {
        $user = new class extends User
        {
            public array $testPermissions = [];

            public function hasSubdivisionAccess(int|string|null $subdivisionId): bool
            {
                return $this->canDo('SUPER_ADMINISTRATOR') || (int) $subdivisionId === 1;
            }

            public function getAvailableSubdivisionIds(): array
            {
                return [1];
            }

            public function canDo($alias, $require = false)
            {
                return count(array_intersect((array) $alias, $this->testPermissions)) > 0;
            }
        };
        $user->id = 999;
        $user->testPermissions = $permissions;
        $this->actingAs($user, 'api');
    }

    private function payload(): array
    {
        return [
            'firstname' => 'Test', 'lastname' => 'User', 'email' => 'test@example.com',
            'login' => 'test.user', 'phone' => '123', 'role_id' => 1,
            'password' => 'secret123456', 'password_confirmation' => 'secret123456',
        ];
    }

    public function test_user_creation_and_editing_persist_login_and_preserve_omitted_password(): void
    {
        $this->signIn(['USER_MANAGE']);
        $payload = $this->payload();
        $response = $this->postJson('/api/admin/users', $payload)
            ->assertOk()->assertJsonPath('data.item.login', 'test.user')
            ->assertJsonMissingPath('data.item.password');
        $id = $response->json('data.item.id');
        $user = User::findOrFail($id);
        $hash = $user->password;
        $this->assertTrue(Hash::check('secret123456', $hash));
        $this->assertSame(1, $user->roles()->sole()->id);

        unset($payload['password'], $payload['password_confirmation']);
        $this->putJson('/api/admin/users/'.$id, $payload)->assertOk();
        $payload['login'] = 'new.login';
        $this->putJson('/api/admin/users/'.$id, $payload)
            ->assertOk()->assertJsonPath('data.item.login', 'new.login');
        $this->assertSame($hash, $user->fresh()->password);
        $this->putJson('/api/admin/users/'.$id, $payload + ['password' => null])->assertOk();
        $this->assertSame($hash, $user->fresh()->password);

        $this->putJson('/api/admin/users/'.$id, $payload + ['password' => 'new-secret123', 'password_confirmation' => 'new-secret123'])->assertOk();
        $this->assertTrue(Hash::check('new-secret123', $user->fresh()->password));
    }

    public function test_login_validation_on_create_and_update(): void
    {
        $this->signIn(['SUPER_ADMINISTRATOR']);
        $payload = $this->payload();
        foreach ([null, '', str_repeat('a', 91), ['bad']] as $login) {
            $this->postJson('/api/admin/users', array_replace($payload, ['login' => $login]))
                ->assertUnprocessable()->assertJsonStructure(['errors' => ['login']]);
        }
        $this->postJson('/api/admin/users', array_diff_key($payload, ['password' => true, 'password_confirmation' => true]))->assertUnprocessable();
        $id = $this->postJson('/api/admin/users', $payload)->assertOk()->json('data.item.id');
        $this->postJson('/api/admin/users', array_replace($payload, ['email' => 'other@example.com']))->assertUnprocessable();
        $second = array_replace($payload, ['email' => 'other@example.com', 'login' => 'other']);
        $secondId = $this->postJson('/api/admin/users', $second)->assertOk()->json('data.item.id');
        $this->putJson('/api/admin/users/'.$secondId, array_replace($second, ['login' => 'test.user']))->assertUnprocessable();
        $this->putJson('/api/admin/users/'.$id, $payload)->assertOk();
    }

    public function test_password_validation_and_reset_revoke_only_target_tokens(): void
    {
        $this->signIn(['USER_MANAGE']);
        $payload = $this->payload();
        $id = $this->postJson('/api/admin/users', $payload)->assertOk()->json('data.item.id');
        $hash = User::findOrFail($id)->password;
        unset($payload['password'], $payload['password_confirmation']);
        DB::table('oauth_clients')->insert([
            'id' => '00000000-0000-0000-0000-000000000001', 'name' => 'Test client',
            'provider' => 'users', 'redirect_uris' => '[]', 'grant_types' => '[]', 'revoked' => false,
        ]);
        foreach (['target-a' => $id, 'target-b' => $id, 'actor' => 999] as $tokenId => $userId) {
            DB::table('oauth_access_tokens')->insert([
                'id' => $tokenId, 'user_id' => $userId, 'client_id' => '00000000-0000-0000-0000-000000000001', 'revoked' => false,
            ]);
            DB::table('oauth_refresh_tokens')->insert(['id' => 'refresh-'.$tokenId, 'access_token_id' => $tokenId, 'revoked' => false]);
        }
        foreach ([[], ['password' => null], ['password' => '']] as $optional) {
            $this->putJson('/api/admin/users/'.$id, $payload + $optional)->assertOk();
            $this->assertSame($hash, User::findOrFail($id)->password);
            $this->assertDatabaseHas('oauth_access_tokens', ['id' => 'target-a', 'revoked' => false]);
        }
        foreach ([
            ['password' => 'short', 'password_confirmation' => 'short'],
            ['password' => 'replacement123', 'password_confirmation' => 'different123'],
            ['password' => 'replacement123'],
            ['password' => str_repeat('a', 73), 'password_confirmation' => str_repeat('a', 73)],
            ['password' => str_repeat("\u{00E9}", 40), 'password_confirmation' => str_repeat("\u{00E9}", 40)],
        ] as $invalid) {
            $this->putJson('/api/admin/users/'.$id, $payload + $invalid)->assertUnprocessable()
                ->assertJsonStructure(['errors' => ['password']]);
            $this->postJson('/api/admin/users', array_replace($payload, ['login' => 'another', 'email' => 'another@example.com']) + $invalid)
                ->assertUnprocessable()->assertJsonStructure(['errors' => ['password']]);
        }
        $this->assertSame($hash, User::findOrFail($id)->password);
        $this->putJson('/api/admin/users/'.$id, $payload + [
            'password' => 'replacement123', 'password_confirmation' => 'replacement123',
        ])->assertOk()->assertJsonMissingPath('data.item.password');
        $this->assertTrue(Hash::check('replacement123', User::findOrFail($id)->password));
        $this->assertFalse(Hash::check('secret123456', User::findOrFail($id)->password));
        foreach (['target-a', 'target-b'] as $tokenId) {
            $this->assertDatabaseHas('oauth_access_tokens', ['id' => $tokenId, 'revoked' => true]);
            $this->assertDatabaseHas('oauth_refresh_tokens', ['id' => 'refresh-'.$tokenId, 'revoked' => true]);
        }
        $this->assertDatabaseHas('oauth_access_tokens', ['id' => 'actor', 'revoked' => false]);
        $this->assertDatabaseHas('oauth_refresh_tokens', ['id' => 'refresh-actor', 'revoked' => false]);
    }

    public function test_six_digit_password_is_accepted_for_creation_and_editing(): void
    {
        $this->signIn(['USER_MANAGE']);
        $payload = array_replace($this->payload(), ['password' => '123456', 'password_confirmation' => '123456']);
        $id = $this->postJson('/api/admin/users', $payload)->assertOk()->json('data.item.id');
        $this->assertTrue(Hash::check('123456', User::findOrFail($id)->password));
        $this->putJson('/api/admin/users/'.$id, array_replace($payload, ['password' => '654321', 'password_confirmation' => '654321']))->assertOk();
        $this->assertTrue(Hash::check('654321', User::findOrFail($id)->password));
        $this->putJson('/api/admin/users/'.$id, array_replace($payload, ['password' => '12345', 'password_confirmation' => '12345']))->assertUnprocessable();
    }

    public function test_manager_cannot_reset_a_super_admin_password_by_assigning_a_lower_role(): void
    {
        $user = new User;
        $user->forceFill(['email' => 'super@example.com', 'login' => 'super', 'password' => Hash::make('original-password')])->save();
        DB::table('roles')->insert(['id' => 2, 'alias' => 'SUPER_ADMINISTRATOR', 'title' => 'Super']);
        $user->roles()->sync([2]);
        $this->signIn(['USER_MANAGE', 'ROLES_MANAGE']);
        $this->putJson('/api/admin/users/'.$user->id, $this->payload())->assertUnprocessable();
        $this->assertTrue(Hash::check('original-password', $user->fresh()->password));
    }

    public function test_create_update_routes_require_manage_permission(): void
    {
        $user = new User;
        $user->forceFill(['email' => 'existing@example.com', 'login' => 'existing', 'password' => Hash::make('secret')])->save();
        $this->postJson('/api/admin/users', $this->payload())->assertUnauthorized();
        foreach ([[], ['USERS_ACCESS'], ['USER_ACCESS'], ['USERS_MANAGE']] as $permissions) {
            $this->signIn($permissions);
            $this->postJson('/api/admin/users', $this->payload())->assertForbidden();
            $this->putJson('/api/admin/users/'.$user->id, $this->payload())->assertForbidden();
        }
        $this->assertSame('existing', $user->fresh()->login);
    }

    public function test_real_authentication_uses_login_not_email(): void
    {
        $user = new User;
        $user->forceFill(['email' => 'person@example.com', 'login' => 'person', 'password' => Hash::make('secret')])->save();
        $this->assertTrue(Auth::guard('web')->attempt(['login' => 'person', 'password' => 'secret']));
        $this->assertSame($user->id, Auth::guard('web')->id());
        Auth::guard('web')->logout();
        $this->postJson(route('api.auths.login'), ['email' => 'person@example.com', 'password' => 'secret'])
            ->assertUnprocessable()->assertJsonStructure(['errors' => ['login']]);
        $this->postJson(route('api.auths.login'), ['login' => 'person@example.com', 'password' => 'secret'])->assertForbidden();
        $this->postJson(route('api.auths.login'), ['login' => 'person', 'password' => 'wrong'])->assertForbidden();
    }

    public function test_api_login_passes_only_login_and_password_and_returns_token(): void
    {
        $request = LoginRequest::create('/', 'POST', ['login' => 'person', 'email' => 'ignored@example.com', 'password' => 'secret']);
        $request->setUserResolver(fn () => new class
        {
            public function createToken($name)
            {
                return (object) ['accessToken' => 'test-token', 'token' => (object) ['expires_at' => '2026-12-01 00:00:00']];
            }
        });
        Auth::shouldReceive('attempt')->once()->with(['login' => 'person', 'password' => 'secret'])->andReturnTrue();
        $response = app(LoginController::class)->login($request);
        $this->assertSame(200, $response->status());
        $this->assertSame('test-token', $response->getData(true)['data']['api_token']);
    }

    public function test_subdivisions_sync_status_details_and_paginated_filters(): void
    {
        DB::table('subdivisions')->insert([['id' => 1, 'title' => 'Main'], ['id' => 2, 'title' => 'Other']]);
        $this->signIn(['SUPER_ADMINISTRATOR']);
        $data = $this->payload() + ['subdivision_ids' => [1, 2], 'status' => '0'];
        $id = $this->postJson('/api/admin/users', $data)->assertOk()
            ->assertJsonPath('data.item.status', '0')
            ->assertJsonPath('data.item.subdivision_ids', [1, 2])->json('data.item.id');
        $this->getJson('/api/admin/users/'.$id)->assertOk()
            ->assertJsonPath('data.item.role_id', 1)->assertJsonCount(1, 'data.item.roles')
            ->assertJsonCount(2, 'data.item.subdivisions');
        unset($data['password'], $data['password_confirmation'], $data['subdivision_ids']);
        $this->putJson('/api/admin/users/'.$id, $data)->assertOk()->assertJsonPath('data.item.subdivision_ids', [1, 2]);
        $this->putJson('/api/admin/users/'.$id, $data + ['subdivision_ids' => []])
            ->assertOk()->assertJsonPath('data.item.subdivision_ids', []);
        $this->putJson('/api/admin/users/'.$id, $data + ['subdivision_ids' => [1, 1]])->assertUnprocessable();
        $this->putJson('/api/admin/users/'.$id, $data + ['subdivision_ids' => [999]])->assertUnprocessable();
        $this->postJson('/api/admin/users', array_replace($this->payload(), ['email' => 'active@example.com', 'login' => 'active']))->assertOk();
        $this->getJson('/api/admin/users?status=0&login=test&sort=-lastname&page_size=1')
            ->assertOk()->assertJsonPath('data.items.total', 1)
            ->assertJsonPath('data.items.data.0.subdivision_ids', []);
        $this->getJson('/api/admin/users?sort=-id&page_size=1&page=2')->assertOk()->assertJsonPath('data.items.data.0.id', $id);
        $this->postJson('/api/admin/users', array_replace($this->payload(), ['email' => 'another@example.com', 'login' => 'another']))->assertOk();
        $this->getJson('/api/admin/users/form?page_size=1')->assertOk()->assertJsonCount(2, 'data.items');
        $this->getJson('/api/admin/users?sort=password')->assertUnprocessable();
        $this->signIn(['USERS_ACCESS']);
        $this->getJson('/api/admin/users/'.$id)->assertOk();
        $this->deleteJson('/api/admin/users/'.$id)->assertForbidden();
        $this->signIn([]);
        $this->getJson('/api/admin/users/'.$id)->assertForbidden();
        $this->signIn(['USER_MANAGE']);
        $this->deleteJson('/api/admin/users/'.$id)->assertOk();
        $this->assertDatabaseHas('users', ['id' => $id, 'status' => '0']);
    }

    public function test_user_manager_cannot_grant_foreign_subdivisions_or_higher_roles(): void
    {
        DB::table('subdivisions')->insert([['id' => 1, 'title' => 'Main'], ['id' => 2, 'title' => 'Other']]);
        DB::table('permissions')->insert(['id' => 1, 'alias' => 'ROLES_MANAGE', 'title' => 'Role manager']);
        DB::table('roles')->insert(['id' => 2, 'alias' => 'powerful', 'title' => 'Powerful']);
        DB::table('role_permissions')->insert(['role_id' => 2, 'permission_id' => 1]);
        $this->signIn(['USER_MANAGE']);
        $this->postJson('/api/admin/users', $this->payload() + ['subdivision_ids' => [2]])->assertUnprocessable();
        $this->postJson('/api/admin/users', array_replace($this->payload(), ['role_id' => 2]))->assertUnprocessable();
        $this->assertDatabaseCount('users', 0);
        $id = $this->postJson('/api/admin/users', $this->payload() + ['subdivision_ids' => [1]])->assertOk()->json('data.item.id');
        DB::table('role_user')->where('user_id', $id)->update(['role_id' => 2]);
        $this->putJson('/api/admin/users/'.$id, $this->payload())->assertUnprocessable();
        DB::table('role_user')->where('user_id', $id)->update(['role_id' => 1]);
        DB::table('users_subdivisions')->where('user_id', $id)->update(['subdivision_id' => 2]);
        $this->putJson('/api/admin/users/'.$id, $this->payload())->assertUnprocessable();
    }

    public function test_menu_requires_permission_link_and_targeted_seeder_is_repeatable(): void
    {
        foreach (glob(app_path('Modules/Admin/Menu/Migrations/*.php')) as $path) {
            (require $path)->up();
        }
        DB::table('permissions')->insert(['id' => 1, 'alias' => 'SUPER_ADMINISTRATOR', 'title' => 'Super']);
        DB::table('role_permissions')->insert(['role_id' => 1, 'permission_id' => 1]);
        $user = new User;
        $user->forceFill(['login' => 'super', 'email' => 'super@example.com', 'password' => Hash::make('secret')])->save();
        $user->roles()->sync([1]);
        $this->actingAs($user, 'api');
        $parentId = DB::table('menus')->insertGetId(['title' => 'Доступы', 'path' => '#', 'type' => 'admin', 'parent' => 0, 'sort_order' => 1]);
        $menuId = DB::table('menus')->insertGetId(['title' => 'New menu', 'path' => 'new-menu', 'type' => 'admin', 'parent' => 0, 'sort_order' => 2]);
        $this->getJson('/api/admin/menus')->assertOk()->assertJsonCount(0, 'data.items');
        DB::table('permission_menu')->insert(['permission_id' => 1, 'menu_id' => $menuId]);
        $this->getJson('/api/admin/menus')->assertOk()->assertJsonCount(1, 'data.items');

        $this->seed(\Database\Seeders\UsersSubdivisionsMenusSeeder::class);
        $count = DB::table('permission_menu')->count();
        $this->seed(\Database\Seeders\UsersSubdivisionsMenusSeeder::class);
        $this->assertSame($count, DB::table('permission_menu')->count());
        $this->assertDatabaseCount('menus', 4);
        $this->assertDatabaseHas('menus', ['path' => 'users', 'parent' => $parentId]);
        $this->assertDatabaseHas('menus', ['path' => 'subdivisions', 'parent' => $parentId]);
        $this->getJson('/api/admin/menus')->assertOk()->assertJsonCount(4, 'data.items');
    }

    public function test_subdivision_validation_storage_scope_and_pagination(): void
    {
        (require app_path('Modules/Admin/OutlayGroupNames/Migrations/2023_08_28_090538_create_outlay_group_names_table.php'))->up();
        DB::table('subdivisions')->insert([['id' => 1, 'title' => 'Main'], ['id' => 2, 'title' => 'Other']]);
        DB::table('outlay_group_names')->insert(['id' => 1, 'name' => 'Hall', 'subdivision_id' => 1]);
        Storage::fake('images');
        Storage::disk('images')->put('logo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
        $this->signIn(['SUPER_ADMINISTRATOR']);
        $data = ['title' => 'New branch', 'amo_ids' => '123, 456', 'site_link' => 'example.test', 'logo' => 'logo.png',
            'description' => '<p>Branch</p>', 'default_product_group_id_for_amo' => 1];
        $id = $this->postJson('/api/admin/subdivisions', $data)->assertOk()
            ->assertJsonPath('data.item.site_link', 'example.test')->json('data.item.id');
        $this->putJson('/api/admin/subdivisions/'.$id, array_replace($data, ['logo' => null]))->assertOk()->assertJsonPath('data.item.logo', null);
        $this->getJson('/api/admin/subdivisions?title=branch&amo_ids=123&site_link=example&sort=-title&page_size=1')
            ->assertOk()->assertJsonPath('data.items.total', 1);
        foreach (['../secret.png', '/logo.png', 'C:\\logo.png', 'https://example.test/logo.png', '%2e%2e/secret', 'missing.png', ['bad']] as $path) {
            $this->postJson('/api/admin/subdivisions', array_replace($data, ['logo' => $path]))->assertUnprocessable();
        }
        $this->signIn(['SUBDIVISIONS_ACCESS']);
        $this->getJson('/api/admin/subdivisions')->assertOk()->assertJsonPath('data.items.total', 1);
        $this->getJson('/api/admin/subdivisions/2')->assertForbidden();
        $this->postJson('/api/admin/subdivisions', $data)->assertForbidden();
        $this->signIn(['SUBDIVISIONS_MANAGE']);
        $this->putJson('/api/admin/subdivisions/2', $data)->assertForbidden();
        $this->deleteJson('/api/admin/subdivisions/2')->assertForbidden();
    }
}
