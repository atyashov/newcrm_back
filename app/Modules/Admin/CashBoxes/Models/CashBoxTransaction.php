<?php

namespace App\Modules\Admin\CashBoxes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashBoxTransaction extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'occurred_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function cashBox(): BelongsTo
    {
        return $this->belongsTo(CashBox::class);
    }
}
