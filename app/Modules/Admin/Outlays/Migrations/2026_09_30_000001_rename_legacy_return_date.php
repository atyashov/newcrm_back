<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('outlays', 'return date') && ! Schema::hasColumn('outlays', 'return_date')) {
            DB::statement('ALTER TABLE `outlays` RENAME COLUMN `return date` TO `return_date`');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('outlays', 'return_date') && ! Schema::hasColumn('outlays', 'return date')) {
            DB::statement('ALTER TABLE `outlays` RENAME COLUMN `return_date` TO `return date`');
        }
    }
};
