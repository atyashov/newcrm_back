<?php

namespace App\Modules\Admin\CashBoxes\Models;

use App\Core\Traits\HasFilter;
use App\Modules\Admin\PaymentMethods\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashBox extends Model
{
    use HasFactory,
        HasFilter;

    protected $fillable = [
        'title',
        'opening_balance',
        'payment_method_id',
        'sort_order',
        'is_dashboard',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'is_dashboard' => 'boolean',
    ];

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function subdivision(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Admin\Subdivisions\Models\Subdivision::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CashBoxTransaction::class);
    }
}
