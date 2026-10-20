<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('outlay_groups')->exists()
            || DB::table('outlays')->whereNotNull('product_groups')->exists()) {
            throw new RuntimeException('Legacy outlay composition must be migrated before normalizing its schema.');
        }

        Schema::table('outlay_groups', function (Blueprint $table) {
            $table->dropColumn('data');
            $table->foreignId('outlay_group_name_id')->constrained('outlay_group_names')->restrictOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->index(['outlay_id', 'position']);
        });
        if (DB::getDriverName() === 'mysql') {
            Schema::table('outlay_groups', function (Blueprint $table) {
                $table->dropForeign(['outlay_id']);
                $table->foreign('outlay_id')->references('id')->on('outlays')->cascadeOnDelete();
            });
        }

        Schema::create('outlay_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlay_group_id')->constrained('outlay_groups')->cascadeOnDelete();
            $table->unsignedInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->string('product_title_snapshot');
            $table->decimal('price', 12, 2);
            $table->decimal('quantity', 12, 3);
            $table->decimal('total_cost', 12, 2);
            $table->text('description')->nullable();
            $table->boolean('is_rent')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->index(['outlay_group_id', 'position']);
        });
        Schema::create('outlay_item_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlay_item_id')->constrained('outlay_items')->cascadeOnDelete();
            $table->string('path', 2048);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
        Schema::table('outlays', function (Blueprint $table) {
            $table->dropColumn('product_groups');
            $table->decimal('total_cost', 12, 2)->default(0)->change();
            $table->decimal('discount_amount', 12, 2)->nullable()->change();
            $table->decimal('percent_discount', 5, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlay_item_images');
        Schema::dropIfExists('outlay_items');
        Schema::table('outlay_groups', function (Blueprint $table) {
            $table->dropForeign(['outlay_group_name_id']);
            $table->dropIndex(['outlay_id', 'position']);
            $table->dropColumn(['outlay_group_name_id', 'position']);
            $table->json('data')->nullable();
        });
        Schema::table('outlays', function (Blueprint $table) {
            $table->json('product_groups')->nullable();
        });
    }
};
