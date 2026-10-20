<?php

namespace App\Modules\Admin\Outlays\Models;

use Illuminate\Database\Eloquent\Model;

class OutlayAuditEvent extends Model
{
    public const EVENTS = ['created', 'form_updated', 'creator_changed', 'status_changed'];

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'user_snapshot' => 'array', 'from_status' => 'array', 'to_status' => 'array',
        'from_creator' => 'array', 'to_creator' => 'array', 'occurred_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function data(): array
    {
        return [
            'id' => $this->id, 'event' => $this->event, 'source' => $this->source,
            'user' => $this->user_snapshot, 'at' => $this->occurred_at->format('Y-m-d H:i:s'),
            'from_status' => $this->from_status, 'to_status' => $this->to_status,
            'from_creator' => $this->from_creator, 'to_creator' => $this->to_creator,
        ];
    }
}
