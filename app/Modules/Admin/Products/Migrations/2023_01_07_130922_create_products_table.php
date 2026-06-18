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
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('alias')->nullable();
            $table->float('price')->default(0);
            $table->integer('quantity')->default(1)->unsigned();
            $table->boolean('unlimited')->default(false);
            $table->boolean('is_rent')->default(false);
            $table->boolean('is_service')->default(false);
            $table->string('image')->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
        Schema::create('product_description', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->text('description')->nullable();
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
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_description');
    }
};
