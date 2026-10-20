<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outlays', function (Blueprint $table) {
            $table->uuid('public_uuid')->nullable()->unique();
        });
        DB::table('outlays')->whereNull('public_uuid')->select('id')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('outlays')->where('id', $row->id)->whereNull('public_uuid')->update(['public_uuid' => (string) Str::uuid()]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('outlays', function (Blueprint $table) {
            $table->dropUnique(['public_uuid']);
            $table->dropColumn('public_uuid');
        });
    }
};
