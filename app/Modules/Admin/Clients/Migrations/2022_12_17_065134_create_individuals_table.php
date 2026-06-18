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
        Schema::create('individuals', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('phone', 90)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('social', 255)->nullable();
            $table->string('messenger', 255)->nullable();
            $table->integer('personal_sale')->nullable();
            $table->integer('passport_serial')->unsigned()->nullable();
            $table->integer('passport_number')->unsigned()->nullable();
            $table->date('date_of_passport')->nullable();
            $table->string('issued_by')->nullable();
            $table->date('birthday')->nullable();
            $table->string('registration')->nullable();
            $table->string('address')->nullable();
            $table->integer('client_type_id')->unsigned();
            $table->foreign('client_type_id')->references('id')->on('client_types')->onDelete('cascade');

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
        Schema::dropIfExists('individuals');
    }
};
