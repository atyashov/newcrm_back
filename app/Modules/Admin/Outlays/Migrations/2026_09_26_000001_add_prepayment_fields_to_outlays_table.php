<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outlays', function (Blueprint $table) {
            $table->decimal('prepayment_amount', 12, 2)->nullable()->after('prepayment');
            $table->dateTime('prepayment_date')->nullable()->after('prepayment_amount');
        });
    }

    public function down(): void
    {
        Schema::table('outlays', function (Blueprint $table) {
            $table->dropColumn(['prepayment_amount', 'prepayment_date']);
        });
    }
};
