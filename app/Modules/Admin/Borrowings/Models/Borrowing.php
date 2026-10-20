<?php

namespace App\Modules\Admin\Borrowings\Models;

use App\Core\Traits\HasFilter;
use App\Modules\Admin\CashBoxes\Models\CashBox;
use App\Modules\Admin\Subdivisions\Models\Subdivision;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Borrowing extends Model
{
    use HasFactory,
        HasFilter;

    protected $fillable = [
        'title',
        'amount',
        'signing',
        'date',
        'subdivision_sender_id',
        'subdivision_receiver_id',
        'cash_box_sender_id',
        'cash_box_receiver_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date:Y-m-d',
    ];

    public function subdivisionSender(): BelongsTo
    {
        return $this->belongsTo(Subdivision::class, 'subdivision_sender_id');
    }

    public function subdivisionReceiver(): BelongsTo
    {
        return $this->belongsTo(Subdivision::class, 'subdivision_receiver_id');
    }

    public function cashBoxSender(): BelongsTo
    {
        return $this->belongsTo(CashBox::class, 'cash_box_sender_id');
    }

    public function cashBoxReceiver(): BelongsTo
    {
        return $this->belongsTo(CashBox::class, 'cash_box_receiver_id');
    }
}
