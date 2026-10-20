<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_entities', fn (Blueprint $table) => $table->string('ogrn', 15)->nullable());
    }

    public function down(): void
    {
        Schema::table('legal_entities', fn (Blueprint $table) => $table->dropColumn('ogrn'));
    }
};
