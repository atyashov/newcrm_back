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
        Schema::create('borrowings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->decimal('amount')->default(0);
            $table->text('signing')->nullable();
            $table->date('date');
            $table->unsignedInteger('subdivision_sender_id');
            $table->unsignedInteger('subdivision_receiver_id');
            $table->unsignedBigInteger('cash_box_sender_id');
            $table->unsignedBigInteger('cash_box_receiver_id');

            $table->index(['subdivision_sender_id']);
            $table->index(['subdivision_receiver_id']);
            $table->index(['cash_box_sender_id']);
            $table->index(['cash_box_receiver_id']);

            $table->foreign('subdivision_sender_id')
                ->references('id')
                ->on('subdivisions')
                ->onDelete('cascade');

            $table->foreign('subdivision_receiver_id')
                ->references('id')
                ->on('subdivisions')
                ->onDelete('cascade');

            $table->foreign('cash_box_sender_id')
                ->references('id')
                ->on('cash_boxes')
                ->onDelete('cascade');

            $table->foreign('cash_box_receiver_id')
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
        Schema::dropIfExists('borrowings');
    }
};
