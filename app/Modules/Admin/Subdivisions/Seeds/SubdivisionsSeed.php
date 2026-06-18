<?php

namespace App\Modules\Admin\Subdivisions\Seeds;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubdivisionsSeed extends Seeder
{
    public function run(){
        DB::table('subdivisions')->insert([
            [
                'title' => 'Впрокат',
            ],
            [
                'title' => 'FlowersGift',
            ],
            [
                'title' => 'Слова',
            ],
            [
                'title' => 'TentMobile',
            ],

        ]);
    }
}
