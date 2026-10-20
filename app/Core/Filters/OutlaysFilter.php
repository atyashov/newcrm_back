<?php

namespace App\Core\Filters;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class OutlaysFilter extends Filter
{
    public const array KEYS_TO_BOOL = [
        'is_rent',
        'is_paid',
        'prepayment',
    ];

    public const array KEYS_TO_INT = [
        'subdivision_id',
        'creator_id',
        'status_id',
        'client_type_id',
        'client_id',
        'source_id',
        'subject_id',
        'percent_discount',
        'payment_method_id',
        'prepayment_method_id',
    ];

    public const array KEYS_TO_DATE = [
        'execution_date',
        'return_date',
        'date_of_paid',
        'date_start_from',
        'date_start_to',
        'dateStartFrom',
        'dateStartTo',
        'dateStart',
        'dateEnd',
        'return_date_from', 'return_date_to',
    ];

    protected function title(string $value): Builder
    {
        return $this->like('title', $value);
    }

    protected function id($value): Builder
    {
        return $this->builder->whereKey($value);
    }

    protected function selfPickup($value): Builder
    {
        return $this->builder->where('self_pickup', $value);
    }

    protected function selfReturn($value): Builder
    {
        return $this->builder->where('self_return', $value);
    }

    protected function returnDateComment(string $value): Builder
    {
        return $this->like('return_date_comment', $value);
    }

    protected function returnDateFrom(CarbonImmutable $value): Builder
    {
        return $this->builder->where('return_date', '>=', $this->startOfDay($value));
    }

    protected function returnDateTo(CarbonImmutable $value): Builder
    {
        return $this->builder->where('return_date', '<=', $this->endOfDay($value));
    }

    protected function statusIds(array $value): Builder
    {
        return $value ? $this->builder->whereIn('status_id', $value) : $this->builder;
    }

    protected function performerIds(array $value): Builder
    {
        return $this->assigned('performers', $value);
    }

    protected function returnPerformerIds(array $value): Builder
    {
        return $this->assigned('returnPerformers', $value);
    }

    protected function assemblerIds(array $value): Builder
    {
        return $this->assigned('assemblers', $value);
    }

    protected function acceptorIds(array $value): Builder
    {
        return $this->assigned('acceptors', $value);
    }

    private function assigned(string $relation, array $ids): Builder
    {
        return $ids ? $this->builder->whereHas($relation, fn ($query) => $query->whereIn('users.id', $ids)) : $this->builder;
    }

    protected function storeIds(array $ids): Builder
    {
        return $ids ? $this->builder->whereHas('groups.items.product', fn ($query) => $query->whereIn('store_id', $ids)) : $this->builder;
    }

    protected function executionDate(CarbonImmutable $value): Builder
    {
        return $this->builder->where('execution_date', '>=', $this->startOfDay($value));
    }

    protected function returnDate(CarbonImmutable $value): Builder
    {
        return $this->builder->where('return_date', '<=', $this->endOfDay($value));
    }

    protected function dateOfPaid(CarbonImmutable $value): Builder
    {
        return $this->builder->whereDate('date_of_paid', $value);
    }

    protected function dateStartFrom(CarbonImmutable $value): Builder
    {
        return $this->builder->where('execution_date', '>=', $this->startOfDay($value));
    }

    protected function dateStartTo(CarbonImmutable $value): Builder
    {
        return $this->builder->where('execution_date', '<=', $this->endOfDay($value));
    }

    protected function dateStart(CarbonImmutable $value): Builder
    {
        return $this->dateStartFrom($value);
    }

    protected function dateEnd(CarbonImmutable $value): Builder
    {
        return $this->dateStartTo($value);
    }

    protected function dateComment(string $value): Builder
    {
        return $this->like('date_comment', $value);
    }

    protected function subdivisionId(int $value): Builder
    {
        return $this->builder->where('subdivision_id', $value);
    }

    protected function creatorId(int $value): Builder
    {
        return $this->builder->where('creator_id', $value);
    }

    protected function statusId(int $value): Builder
    {
        return $this->builder->where('status_id', $value);
    }

    protected function clientTypeId(int $value): Builder
    {
        return $this->builder->where('client_type_id', $value);
    }

    protected function clientId(int $value): Builder
    {
        return $this->builder->where('client_id', $value);
    }

    protected function partnerEntity(string $value): Builder
    {
        return $this->like('partner_entity', $value);
    }

    protected function sourceId(int $value): Builder
    {
        return $this->builder->where('source_id', $value);
    }

    protected function subjectId(int $value): Builder
    {
        return $this->builder->where('subject_id', $value);
    }

    protected function isRent(bool $value): Builder
    {
        return $this->builder->where('is_rent', $value);
    }

    protected function recipientName(string $value): Builder
    {
        return $this->like('recipient_name', $value);
    }

    protected function recipientPhone(string $value): Builder
    {
        return $this->like('recipient_phone', $value);
    }

    protected function address(string $value): Builder
    {
        return $this->like('address', $value);
    }

    protected function percentDiscount(int $value): Builder
    {
        return $this->builder->where('percent_discount', $value);
    }

    protected function discountAmount(string|float|int $value): Builder
    {
        return $this->builder->where('discount_amount', (float) $value);
    }

    protected function isPaid(bool $value): Builder
    {
        return $this->builder->where('is_paid', $value);
    }

    protected function paymentMethodId(int $value): Builder
    {
        return $this->builder->where('payment_method_id', $value);
    }

    protected function prepayment(bool $value): Builder
    {
        return $this->builder->where('prepayment', $value);
    }

    protected function prepaymentMethodId(int $value): Builder
    {
        return $this->builder->where('prepayment_method_id', $value);
    }

    protected function totalCost(string|float|int $value): Builder
    {
        return $this->builder->where('total_cost', (float) $value);
    }

    protected function routeLink(string $value): Builder
    {
        return $this->like('route_link', $value);
    }

    private function like(string $field, string $value): Builder
    {
        return $this->builder->where($field, 'like', '%'.urldecode($value).'%');
    }

    private function startOfDay(CarbonImmutable $value): string
    {
        return $value->startOfDay()->format('Y-m-d H:i:s');
    }

    private function endOfDay(CarbonImmutable $value): string
    {
        return $value->endOfDay()->format('Y-m-d H:i:s');
    }
}
