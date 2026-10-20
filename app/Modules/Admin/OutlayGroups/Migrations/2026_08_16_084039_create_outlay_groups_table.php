<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('outlay_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('outlay_id')->index();
            $table->foreign('outlay_id')
                ->references('id')
                ->on('outlays')
                ->cascadeOnDelete();
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outlay_groups');
    }
};
