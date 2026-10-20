<?php

namespace App\Modules\Admin\Outlays\Models;

use App\Modules\Admin\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

trait HasFieldEditors
{
    public function initializeHasFieldEditors(): void
    {
        $this->append('editors');
        foreach (static::EDITED_FIELDS as $field) {
            $this->mergeCasts([$field.'_updated_at' => 'datetime:Y-m-d H:i:s']);
            $this->makeHidden([$field.'_updated_by_id', $field.'_updated_at', Str::camel($field).'Editor']);
        }
    }

    protected function fieldEditor(string $field): BelongsTo
    {
        return $this->belongsTo(User::class, $field.'_updated_by_id')->select('id', 'login', 'firstname', 'lastname');
    }

    public function getEditorsAttribute(): array
    {
        $editors = [];
        foreach (static::EDITED_FIELDS as $field) {
            $editors[$field] = [
                'user' => $this->{Str::camel($field).'Editor'},
                'updated_at' => $this->{$field.'_updated_at'}?->format('Y-m-d H:i:s'),
            ];
        }

        return $editors;
    }

    public function applyEditedFields(array $values, int $actorId): bool
    {
        $changed = false;
        foreach (static::EDITED_FIELDS as $field) {
            if (! array_key_exists($field, $values)) {
                continue;
            }
            $this->setAttribute($field, $values[$field]);
            if ($this->isDirty($field)) {
                $this->setAttribute($field.'_updated_by_id', $actorId);
                $this->setAttribute($field.'_updated_at', now());
                $changed = true;
            }
        }

        return $changed;
    }
}
