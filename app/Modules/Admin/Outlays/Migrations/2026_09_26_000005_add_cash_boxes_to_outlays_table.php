<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outlays', function (Blueprint $table) {
            $table->foreignId('payment_cash_box_id')->nullable()->after('payment_method_id')
                ->constrained('cash_boxes')->restrictOnDelete();
            $table->foreignId('prepayment_cash_box_id')->nullable()->after('prepayment_method_id')
                ->constrained('cash_boxes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('outlays', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prepayment_cash_box_id');
            $table->dropConstrainedForeignId('payment_cash_box_id');
        });
    }
};
