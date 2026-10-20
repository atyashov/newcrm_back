<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outlays', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->dateTime('execution_date');
            $table->dateTime('return_date')->nullable();
            $table->text('date_comment')->nullable();
            $table->integer('subdivision_id')->unsigned();
            $table->foreign('subdivision_id')->references('id')->on('subdivisions')->onDelete('cascade');
            $table->bigInteger('creator_id')->unsigned();
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('cascade');
            $table->json('responsible_workers');
            $table->integer('status_id')->unsigned();
            $table->foreign('status_id')->references('id')->on('statuses')->onDelete('cascade');
            $table->integer('client_type_id')->unsigned()->nullable();
            $table->foreign('client_type_id')->references('id')->on('client_types')->onDelete('cascade');
            $table->integer('client_id')->unsigned();
            $table->string('partner_entity', 90);
            $table->integer('source_id')->unsigned()->nullable();
            $table->foreign('source_id')->references('id')->on('sources');
            $table->integer('subject_id')->unsigned()->nullable();
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->boolean('is_rent')->default(false);
            $table->string('recipient_name', 255)->nullable();
            $table->string('recipient_phone', 90)->nullable();
            $table->string('address', 255)->nullable();
            $table->tinyInteger('percent_discount')->nullable();
            $table->float('discount_amount')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->date('date_of_paid')->nullable();
            $table->integer('payment_method_id')->unsigned()->nullable();
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('cascade');
            $table->boolean('prepayment')->default(false);
            $table->integer('prepayment_method_id')->unsigned()->nullable();
            $table->foreign('prepayment_method_id')->references('id')->on('payment_methods')->onDelete('cascade');
            $table->json('product_groups')->nullable();
            $table->float('total_cost', 12)->default(0);
            $table->string('route_link')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('outlays');
    }
};
