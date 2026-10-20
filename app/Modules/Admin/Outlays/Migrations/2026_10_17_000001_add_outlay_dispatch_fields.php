<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outlays', function (Blueprint $table) {
            $table->boolean('self_pickup')->default(false);
            $table->boolean('self_return')->default(false);
            $table->text('return_date_comment')->nullable();
            $table->index(['subdivision_id', 'return_date']);
        });
        Schema::create('outlay_assignments', function (Blueprint $table) {
            $table->unsignedInteger('outlay_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 32);
            $table->foreign('outlay_id')->references('id')->on('outlays')->cascadeOnDelete();
            $table->primary(['outlay_id', 'role', 'user_id']);
            $table->index(['role', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlay_assignments');
        Schema::table('outlays', function (Blueprint $table) {
            $table->dropIndex(['subdivision_id', 'return_date']);
            $table->dropColumn(['self_pickup', 'self_return', 'return_date_comment']);
        });
    }
};
