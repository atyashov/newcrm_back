<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::transaction(function () {
            $login = (string) env('CRM_ADMIN_LOGIN', 'admin');
            $user = DB::table('users')->where('login', $login)->first();
            if ($user === null) {
                $email = env('CRM_ADMIN_EMAIL');
                $password = env('CRM_ADMIN_PASSWORD');
                if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)
                    || ! is_string($password) || strlen($password) < 6 || $login === '' || strlen($login) > 90) {
                    throw new \RuntimeException('Set CRM_ADMIN_LOGIN, CRM_ADMIN_EMAIL and CRM_ADMIN_PASSWORD (at least 6 characters) before creating the administrator.');
                }
                $id = DB::table('users')->insertGetId([
                    'firstname' => 'admin', 'email' => $email, 'login' => $login,
                    'password' => Hash::make($password), 'status' => '1',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            } else {
                $id = $user->id;
            }

            if (! DB::table('roles')->where('alias', 'SUPER_ADMINISTRATOR')->exists()) {
                DB::table('roles')->insert(['alias' => 'SUPER_ADMINISTRATOR', 'title' => 'Super Administrator']);
            }
            $role = DB::table('roles')->where('alias', 'SUPER_ADMINISTRATOR')->first();
            if (! DB::table('permissions')->where('alias', 'SUPER_ADMINISTRATOR')->exists()) {
                DB::table('permissions')->insert(['alias' => 'SUPER_ADMINISTRATOR', 'title' => 'Super Administrator']);
            }
            $permission = DB::table('permissions')->where('alias', 'SUPER_ADMINISTRATOR')->first();
            DB::table('role_permissions')->updateOrInsert(['role_id' => $role->id, 'permission_id' => $permission->id]);
            DB::table('role_user')->updateOrInsert(['user_id' => $id, 'role_id' => $role->id]);
        });
    }
}
