<?php

namespace App\Modules\Admin\Expenses\Models;

use App\Core\Traits\HasFilter;
use App\Modules\Admin\CashBoxes\Models\CashBox;
use App\Modules\Admin\ExpenseGroups\Models\ExpenseGroup;
use App\Modules\Admin\Subdivisions\Models\Subdivision;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory,
        HasFilter;

    protected $fillable = [
        'date',
        'price',
        'quantity',
        'amount',
        'subdivision_id',
        'expense_group_id',
        'cash_box_id',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'amount' => 'decimal:2',
    ];

    public function subdivision(): BelongsTo
    {
        return $this->belongsTo(Subdivision::class);
    }

    public function expenseGroup(): BelongsTo
    {
        return $this->belongsTo(ExpenseGroup::class);
    }

    public function cashBox(): BelongsTo
    {
        return $this->belongsTo(CashBox::class);
    }
}
