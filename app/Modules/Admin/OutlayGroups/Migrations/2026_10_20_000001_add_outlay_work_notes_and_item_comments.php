<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL DDL is not transactional: a failed run can leave columns/tables behind.
        foreach (['assembled', 'uploaded', 'retrieved', 'checked', 'lost', 'defective'] as $field) {
            if (! Schema::hasColumn('outlay_item_stages', $field.'_updated_by_id')) {
                Schema::table('outlay_item_stages', fn (Blueprint $table) => $table->foreignId($field.'_updated_by_id')->nullable());
            }
            if (! Schema::hasColumn('outlay_item_stages', $field.'_updated_at')) {
                Schema::table('outlay_item_stages', fn (Blueprint $table) => $table->timestamp($field.'_updated_at')->nullable());
            }
            $this->ensureForeign('outlay_item_stages', $field.'_updated_by_id', 'users');
        }

        if (! Schema::hasTable('outlay_item_comments')) {
            Schema::create('outlay_item_comments', function (Blueprint $table) {
                $table->foreignId('outlay_item_id')->primary()->constrained('outlay_items')->cascadeOnDelete();
                foreach (['assembly_comment', 'retrieve_comment'] as $field) {
                    $table->text($field)->nullable();
                    $table->foreignId($field.'_updated_by_id')->nullable()->constrained('users')->nullOnDelete();
                    $table->timestamp($field.'_updated_at')->nullable();
                }
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('outlay_work_notes')) {
            Schema::create('outlay_work_notes', function (Blueprint $table) {
                $table->unsignedInteger('outlay_id')->primary();
                foreach (['additional_upload_comment', 'retriever_comment', 'damage_loss_comment'] as $field) {
                    $table->text($field)->nullable();
                    $table->foreignId($field.'_updated_by_id')->nullable();
                    $table->timestamp($field.'_updated_at')->nullable();
                }
                $table->timestamps();
            });
        }

        if (! $this->hasForeign('outlay_work_notes', 'outlay_id')) {
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                Schema::table('outlay_work_notes', fn (Blueprint $table) => $table->unsignedInteger('outlay_id')->change());
            }
            $this->ensureForeign('outlay_work_notes', 'outlay_id', 'outlays', cascade: true);
        }
        $this->ensureForeign('outlay_item_comments', 'outlay_item_id', 'outlay_items', cascade: true);
        foreach (['assembly_comment', 'retrieve_comment'] as $field) {
            $this->ensureForeign('outlay_item_comments', $field.'_updated_by_id', 'users');
        }
        foreach (['additional_upload_comment', 'retriever_comment', 'damage_loss_comment'] as $field) {
            $this->ensureForeign('outlay_work_notes', $field.'_updated_by_id', 'users', 'work_notes_'.$field.'_editor_fk');
        }
    }

    private function hasForeign(string $table, string $column): bool
    {
        return collect(Schema::getForeignKeys($table))->contains(fn ($foreign) => $foreign['columns'] === [$column]);
    }

    private function ensureForeign(string $tableName, string $column, string $target, ?string $name = null, bool $cascade = false): void
    {
        if ($this->hasForeign($tableName, $column)) {
            return;
        }
        Schema::table($tableName, function (Blueprint $table) use ($column, $target, $name, $cascade) {
            $foreign = $table->foreign($column, $name)->references('id')->on($target);
            if ($cascade) {
                $foreign->cascadeOnDelete();
            } else {
                $foreign->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlay_work_notes');
        Schema::dropIfExists('outlay_item_comments');
        Schema::table('outlay_item_stages', function (Blueprint $table) {
            foreach (['assembled', 'uploaded', 'retrieved', 'checked', 'lost', 'defective'] as $field) {
                $table->dropConstrainedForeignId($field.'_updated_by_id');
                $table->dropColumn($field.'_updated_at');
            }
        });
    }
};
