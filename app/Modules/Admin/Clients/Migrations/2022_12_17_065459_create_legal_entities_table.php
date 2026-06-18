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
        Schema::create('legal_entities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('contact_name', 255);
            $table->string('organization_name');
            $table->string('mobile_phone', 90)->nullable();
            $table->string('phone', 90)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('social', 255)->nullable();
            $table->string('messenger', 255)->nullable();
            $table->integer('personal_sale')->nullable();
            $table->string('additional_phone', 90)->nullable();
            $table->integer('client_type_id')->unsigned();
            $table->foreign('client_type_id')->references('id')->on('client_types')->onDelete('cascade');
            $table->integer('tin')->unsigned()->nullable();
            $table->integer('cpr')->unsigned()->nullable();
            $table->string('bank_name')->nullable();
            $table->integer('bik')->unsigned()->nullable();
            $table->integer('k_s')->unsigned()->nullable();
            $table->integer('r_s')->unsigned()->nullable();
            $table->string('legal_address')->nullable();
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
        Schema::dropIfExists('legal_entities');
    }
};
