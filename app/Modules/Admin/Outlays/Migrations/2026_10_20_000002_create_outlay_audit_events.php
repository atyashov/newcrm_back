<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlay_audit_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('outlay_id');
            $table->foreign('outlay_id')->references('id')->on('outlays')->cascadeOnDelete();
            $table->string('event', 40);
            $table->string('source', 20);
            // Snapshots deliberately have no user/status FK: deleting them must not erase history.
            $table->json('user_snapshot')->nullable();
            $table->json('from_status')->nullable();
            $table->json('to_status')->nullable();
            $table->json('from_creator')->nullable();
            $table->json('to_creator')->nullable();
            $table->dateTime('occurred_at');
            $table->index(['outlay_id', 'occurred_at', 'id'], 'outlay_audit_timeline');
            $table->index(['outlay_id', 'event', 'id'], 'outlay_audit_event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlay_audit_events');
    }
};
