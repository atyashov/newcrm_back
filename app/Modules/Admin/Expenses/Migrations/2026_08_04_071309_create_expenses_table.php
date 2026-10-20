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
        Schema::create('expenses', function (Blueprint $table) {
            $table->increments('id');
            $table->date('date');
            $table->decimal('price', 8, 2)->default(0);
            $table->integer('quantity');
            $table->decimal('amount');
            $table->unsignedInteger('subdivision_id');
            $table->unsignedBigInteger('expense_group_id');
            $table->unsignedBigInteger('cash_box_id');

            $table->index('date');
            $table->index('subdivision_id');
            $table->index('expense_group_id');
            $table->index('cash_box_id');

            $table->foreign('subdivision_id')
                ->references('id')
                ->on('subdivisions')
                ->onDelete('cascade');
            $table->foreign('expense_group_id')
                ->references('id')
                ->on('expense_groups')
                ->onDelete('cascade');
            $table->foreign('cash_box_id')
                ->references('id')
                ->on('cash_boxes')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
