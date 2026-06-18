<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreateAdminUser extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('users')->where('firstname', 'admin')->delete();
        //
        DB::table('users')->insert([
            'firstname'=>'admin',
            'lastname'=>'admin',
            'phone'=>'111111111111',
            'email'=>'admin@admin.com',
            'password'=>bcrypt('admin'),
            'status'=>'1',
        ]);

        $user = DB::table('users')
            ->where('firstname', 'admin')
            ->first();

        DB::table('roles')
            ->where('alias', 'SUPER_ADMINISTRATOR')
            ->delete();

        DB::table('roles')->insert([
            'alias'=>'SUPER_ADMINISTRATOR',
            'title'=>'Super Administrator',
        ]);

        $role = DB::table('roles')
            ->where('alias', 'SUPER_ADMINISTRATOR')
            ->first();

        DB::table('permissions')->where('alias', 'SUPER_ADMINISTRATOR')->delete();

        DB::table('permissions')->insert([
            'alias' => 'SUPER_ADMINISTRATOR',
            'title'=>'Super Administrator',
        ]);

        $permission = DB::table('permissions')->where('alias', 'SUPER_ADMINISTRATOR')->first();

        DB::table('role_permissions')->insert([
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);

        DB::table('role_user')
            ->where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->delete();

        DB::table('role_user')->insert([
            'user_id'=>$user->id,
            'role_id'=>$role->id,
        ]);
    }
}
