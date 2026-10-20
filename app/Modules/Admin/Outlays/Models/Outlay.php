<?php

namespace App\Modules\Admin\Outlays\Models;

use App\Core\Traits\HasFilter;
use App\Modules\Admin\CashBoxes\Models\CashBox;
use App\Modules\Admin\OutlayGroups\Models\OutlayGroup;
use App\Modules\Admin\Statuses\Models\Status;
use App\Modules\Admin\Subdivisions\Models\Subdivision;
use App\Modules\Admin\User\Models\User;
use App\Modules\Admin\Warehouses\Models\ProductRental;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Outlay extends Model
{
    use HasFactory, HasFilter;

    protected $fillable = [
        'title', 'execution_date', 'return_date', 'date_comment', 'subdivision_id',
        'creator_id', 'responsible_workers', 'status_id', 'client_type_id', 'client_id',
        'partner_entity', 'source_id', 'subject_id', 'is_rent', 'recipient_name',
        'recipient_phone', 'address', 'percent_discount', 'discount_amount', 'is_paid',
        'date_of_paid', 'payment_method_id', 'prepayment', 'prepayment_amount',
        'prepayment_date', 'prepayment_method_id', 'payment_cash_box_id',
        'prepayment_cash_box_id', 'route_link',
        'self_pickup', 'self_return', 'return_date_comment',
    ];

    protected $casts = [
        'self_pickup' => 'boolean', 'self_return' => 'boolean',
        'execution_date' => 'datetime:Y-m-d H:i:s',
        'return_date' => 'datetime:Y-m-d H:i:s',
        'date_of_paid' => 'date:Y-m-d',
        'prepayment_date' => 'datetime:Y-m-d H:i:s',
        'responsible_workers' => 'array',
        'is_rent' => 'boolean', 'is_paid' => 'boolean', 'prepayment' => 'boolean',
        'percent_discount' => 'decimal:2', 'discount_amount' => 'decimal:2',
        'prepayment_amount' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $outlay) {
            $outlay->public_uuid = (string) Str::uuid();
        });
    }

    public function subdivision(): BelongsTo
    {
        return $this->belongsTo(Subdivision::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(OutlayGroup::class)->orderBy('position')->orderBy('id');
    }

    public function workNotes(): HasOne
    {
        return $this->hasOne(OutlayWorkNote::class)->withDefault();
    }

    public const ASSIGNMENTS = [
        'performer_ids' => 'performers', 'return_performer_ids' => 'returnPerformers',
        'assembler_ids' => 'assemblers', 'acceptor_ids' => 'acceptors',
    ];

    public const DISPLAY_RELATIONS = [
        'creator:id,login,firstname,lastname', 'status',
        'performers:id,login,firstname,lastname', 'returnPerformers:id,login,firstname,lastname',
        'assemblers:id,login,firstname,lastname', 'acceptors:id,login,firstname,lastname',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function performers(): BelongsToMany
    {
        return $this->assignedUsers('performer');
    }

    public function returnPerformers(): BelongsToMany
    {
        return $this->assignedUsers('return_performer');
    }

    public function assemblers(): BelongsToMany
    {
        return $this->assignedUsers('assembler');
    }

    public function acceptors(): BelongsToMany
    {
        return $this->assignedUsers('acceptor');
    }

    private function assignedUsers(string $role): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'outlay_assignments')->withPivotValue('role', $role)->orderBy('users.id');
    }

    public function withAssignmentIds(): self
    {
        foreach (self::ASSIGNMENTS as $field => $relation) {
            $this->setAttribute($field, $this->getRelation($relation)->modelKeys());
        }

        return $this;
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(ProductRental::class);
    }

    public function paymentCashBox(): BelongsTo
    {
        return $this->belongsTo(CashBox::class, 'payment_cash_box_id');
    }

    public function prepaymentCashBox(): BelongsTo
    {
        return $this->belongsTo(CashBox::class, 'prepayment_cash_box_id');
    }
}
