<?php

namespace App\Modules\Admin\Outlays\Services;

use App\Modules\Admin\Outlays\Models\Outlay;
use App\Modules\Admin\Outlays\Models\OutlayAuditEvent;
use App\Modules\Admin\Statuses\Models\Status;
use App\Modules\Admin\User\Models\User;
use Illuminate\Support\Arr;

class OutlayAuditService
{
    public function snapshot(Outlay $outlay): array
    {
        $record = $outlay->fresh(['groups.items.images', ...array_values(Outlay::ASSIGNMENTS)]);
        $assignments = [];
        foreach (Outlay::ASSIGNMENTS as $relation) {
            $assignments[$relation] = $record->{$relation}->modelKeys();
            sort($assignments[$relation]);
        }

        return [
            'fields' => Arr::except($record->getAttributes(), ['created_at', 'updated_at', 'public_uuid']),
            'assignments' => $assignments,
            'groups' => $record->groups->map(fn ($group) => [
                'fields' => Arr::except($group->getAttributes(), ['created_at', 'updated_at']),
                'items' => $group->items->map(fn ($item) => [
                    'fields' => Arr::except($item->getAttributes(), ['created_at', 'updated_at']),
                    'images' => $item->images->pluck('path')->all(),
                ])->all(),
            ])->all(),
        ];
    }

    public function formSaved(Outlay $outlay, ?array $before, $actor): void
    {
        $after = $this->snapshot($outlay);
        if ($before === null) {
            $this->record($outlay, 'created', $actor);
            $this->statusChanged($outlay, null, (int) $outlay->status_id, $actor);

            return;
        }
        if ($before == $after) {
            return;
        }

        $this->record($outlay, 'form_updated', $actor);
        $oldCreator = (int) $before['fields']['creator_id'];
        if ($oldCreator !== (int) $outlay->creator_id) {
            $this->record($outlay, 'creator_changed', $actor, [
                'from_creator' => $this->userSnapshot($oldCreator),
                'to_creator' => $this->userSnapshot((int) $outlay->creator_id),
            ]);
        }
        $this->statusChanged($outlay, (int) $before['fields']['status_id'], (int) $outlay->status_id, $actor);
    }

    public function statusChanged(Outlay $outlay, ?int $from, int $to, $actor): void
    {
        if ($from === $to) {
            return;
        }
        $this->record($outlay, 'status_changed', $actor, [
            'from_status' => $this->statusSnapshot($from),
            'to_status' => $this->statusSnapshot($to),
        ]);
    }

    private function userSnapshot(?int $id): ?array
    {
        if ($id === null) {
            return null;
        }

        return User::query()->select('id', 'login', 'firstname', 'lastname')->find($id)?->only(['id', 'login', 'firstname', 'lastname'])
            ?? ['id' => $id, 'login' => null, 'firstname' => null, 'lastname' => null];
    }

    private function statusSnapshot(?int $id): ?array
    {
        return $id === null ? null : ['id' => $id, 'title' => Status::query()->whereKey($id)->value('title')];
    }

    private function record(Outlay $outlay, string $event, $actor, array $data = []): void
    {
        OutlayAuditEvent::query()->create([
            'outlay_id' => $outlay->id, 'event' => $event,
            'source' => $actor === null ? 'system' : 'user',
            'user_snapshot' => $this->userSnapshot($actor?->id), 'occurred_at' => now(),
            ...$data,
        ]);
    }

    public function list(Outlay $outlay, int $pageSize, int $page, ?string $event = null): array
    {
        $query = OutlayAuditEvent::query()->where('outlay_id', $outlay->id);
        $summary = [];
        foreach (['created' => 'created', 'last_edited' => 'form_updated', 'creator_changed' => 'creator_changed'] as $key => $type) {
            $entry = (clone $query)->where('event', $type)->orderByDesc('occurred_at')->orderByDesc('id')->first();
            $summary[$key] = $entry === null ? null : [
                'user' => $entry->user_snapshot, 'at' => $entry->occurred_at->format('Y-m-d H:i:s'), 'source' => $entry->source,
            ];
        }
        if ($summary['created'] === null && $outlay->created_at !== null) {
            $summary['created'] = ['user' => null, 'at' => $outlay->created_at->format('Y-m-d H:i:s'), 'source' => 'unknown'];
        }
        $items = $query->when($event !== null, fn ($q) => $q->where('event', $event))
            ->orderByDesc('occurred_at')->orderByDesc('id')->paginate($pageSize, ['*'], 'page', $page);
        $items->setCollection($items->getCollection()->map(fn ($entry) => $entry->data()));

        return ['summary' => $summary, 'items' => $items];
    }
}
