<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_boxes', function (Blueprint $table) {
            $table->unsignedInteger('subdivision_id')->nullable()->after('id');
            $table->decimal('opening_balance', 14, 2)->default(0)->after('payment_method_id');
            $table->foreign('subdivision_id')->references('id')->on('subdivisions')->restrictOnDelete();
        });

        DB::table('cash_boxes')->update(['opening_balance' => DB::raw('balance')]);

        if (DB::getDriverName() === 'mysql') {
            Schema::table('cash_boxes', function (Blueprint $table) {
                $table->decimal('balance', 14, 2)->change();
            });
        }

        Schema::create('cash_box_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_box_id')->constrained('cash_boxes')->restrictOnDelete();
            $table->string('source_type', 50);
            $table->unsignedBigInteger('source_id');
            $table->string('source_line', 100);
            $table->string('type', 50);
            $table->string('direction', 10);
            $table->decimal('amount', 14, 2);
            $table->dateTime('occurred_at');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['cash_box_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_box_transactions');

        Schema::table('cash_boxes', function (Blueprint $table) {
            $table->dropForeign(['subdivision_id']);
            $table->dropColumn(['subdivision_id', 'opening_balance']);
        });
    }
};
