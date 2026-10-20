<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlay_item_stages', function (Blueprint $table) {
            $table->foreignId('outlay_item_id')->primary()->constrained('outlay_items')->cascadeOnDelete();
            foreach (['assembled', 'uploaded', 'retrieved', 'checked', 'lost', 'defective'] as $flag) {
                $table->boolean($flag)->default(false);
            }
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlay_item_stages');
    }
};
