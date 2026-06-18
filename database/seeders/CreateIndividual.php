<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateIndividual extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('individuals')->insert([
            'name' => Str::random(10),
            'phone' => Str::random(10),
            'email' => Str::random(10).'@gmail.com',
            'social' => "",
            'messenger' => "@" . Str::random(10),
            'personal_sale' => 5,
            'passport_serial' => "1234",
            'passport_number' => "567890",
            'date_of_passport' => now(),
            'issued_by' => Str::random(10),
            'birthday' => now(),
            'registration' => now(),
            'address' => Str::random(25),
            'client_type_id' => 1,
        ]);
    }
}
