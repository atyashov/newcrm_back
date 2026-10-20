<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('subdivision_id');
            $table->decimal('quantity', 12, 3)->default(0);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->foreign('subdivision_id')->references('id')->on('subdivisions')->restrictOnDelete();
            $table->unique(['product_id', 'subdivision_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_stock_id')->constrained('product_stocks')->restrictOnDelete();
            $table->string('source_type', 50);
            $table->unsignedBigInteger('source_id');
            $table->string('source_line', 100);
            $table->string('type', 50);
            $table->decimal('quantity', 12, 3);
            $table->dateTime('occurred_at');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['product_stock_id', 'occurred_at']);
        });

        Schema::create('product_rentals', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('outlay_id')->nullable();
            $table->unsignedBigInteger('outlay_item_id')->nullable();
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('subdivision_id');
            $table->decimal('quantity', 12, 3);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 30)->default('reserved');
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->foreign('subdivision_id')->references('id')->on('subdivisions')->restrictOnDelete();
            $table->unique('outlay_item_id');
            $table->index(['product_id', 'subdivision_id', 'starts_at', 'ends_at'], 'rentals_availability_index');
            $table->index(['outlay_id', 'status']);
        });

        DB::table('category_product')
            ->join('categories', 'categories.id', '=', 'category_product.category_id')
            ->join('products', 'products.id', '=', 'category_product.product_id')
            ->select('products.id as product_id', 'categories.subdivision_id', 'products.quantity')
            ->distinct()
            ->orderBy('products.id')
            ->each(function (object $row): void {
                DB::table('product_stocks')->insertOrIgnore([
                    'product_id' => $row->product_id,
                    'subdivision_id' => $row->subdivision_id,
                    'quantity' => $row->quantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_rentals');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('product_stocks');
    }
};
