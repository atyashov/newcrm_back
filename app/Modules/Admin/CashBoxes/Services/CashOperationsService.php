<?php

namespace App\Modules\Admin\CashBoxes\Services;

use App\Modules\Admin\CashBoxes\Requests\ListCashOperationsRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CashOperationsService
{
    public function __construct(private readonly DocumentCashFlow $cashFlow) {}

    public function list(ListCashOperationsRequest $request, bool $receiptsOnly = false): array
    {
        $ids = $request->user()->getAvailableSubdivisionIds();
        if ($request->filled('subdivision_id')) {
            abort_unless($request->user()->hasSubdivisionAccess($request->integer('subdivision_id')), 403);
            $ids = [$request->integer('subdivision_id')];
        }
        $events = $receiptsOnly ? $this->cashFlow->payments($ids) : $this->cashFlow->events($ids);
        $query = DB::query()->fromSub($events, 'events')
            ->leftJoin('cash_boxes as cb', 'cb.id', '=', 'events.cash_box_id')
            ->leftJoin('payment_methods as pm', 'pm.id', '=', DB::raw('COALESCE(events.payment_method_id, cb.payment_method_id)'));
        foreach (['type', 'direction', 'cash_box_id', 'source_type', 'source_id', 'outlay_id'] as $field) {
            if ($request->filled($field)) {
                $query->where('events.'.$field, $request->input($field));
            }
        }
        if ($request->filled('payment_method_id')) {
            $query->where('pm.id', $request->integer('payment_method_id'));
        }
        foreach (['source_title', 'outlay_title'] as $field) {
            if ($request->filled($field)) {
                $query->where('events.'.$field, 'like', '%'.$request->input($field).'%');
            }
        }
        foreach (['date' => 'occurred_at', 'execution_date' => 'execution_date'] as $prefix => $column) {
            if ($request->filled($prefix.'_from')) {
                $query->where('events.'.$column, '>=', $request->input($prefix.'_from'));
            }
            if ($request->filled($prefix.'_to')) {
                $query->where('events.'.$column, '<', CarbonImmutable::parse($request->input($prefix.'_to'))->addDay()->format('Y-m-d'));
            }
        }
        $totals = (clone $query)->selectRaw('COALESCE(SUM(CASE WHEN signed_cents > 0 THEN signed_cents ELSE 0 END), 0) AS income, COALESCE(SUM(CASE WHEN signed_cents < 0 THEN -signed_cents ELSE 0 END), 0) AS expense')->first();
        $sort = $request->input('sort') ?: '-occurred_at';
        $column = ltrim($sort, '-');
        $query->select('events.*', 'cb.title as cash_box_title', 'pm.id as method_id', 'pm.title as method_title');
        $query->orderBy($column === 'amount' ? DB::raw('ABS(signed_cents)') : 'events.'.$column, str_starts_with($sort, '-') ? 'desc' : 'asc')
            ->orderBy('events.source_type')->orderBy('events.source_id')->orderBy('events.type');
        $page = $query->paginate($request->integer('page_size', $request->integer('per_page', $request->integer('pageSize', 20))));
        $page->through(function ($row) {
            return [
                'key' => $row->source_type.':'.$row->source_id.':'.$row->type,
                'type' => $row->type, 'direction' => $row->direction,
                'amount' => DocumentCashFlow::money(abs((int) $row->signed_cents)),
                'signed_amount' => DocumentCashFlow::money((int) $row->signed_cents),
                'occurred_at' => CarbonImmutable::parse($row->occurred_at)->format('Y-m-d H:i:s'),
                'subdivision_id' => (int) $row->subdivision_id,
                'cash_box' => ['id' => (int) $row->cash_box_id, 'title' => $row->cash_box_title],
                'payment_method' => $row->method_id ? ['id' => (int) $row->method_id, 'title' => $row->method_title] : null,
                'source_type' => $row->source_type, 'source_id' => (int) $row->source_id, 'source_title' => $row->source_title,
                'outlay_id' => $row->outlay_id === null ? null : (int) $row->outlay_id,
                'outlay_title' => $row->outlay_title,
                'execution_date' => $row->execution_date === null ? null : CarbonImmutable::parse($row->execution_date)->format('Y-m-d H:i:s'),
            ];
        });

        return ['items' => $page, 'totals' => $receiptsOnly
            ? ['amount' => DocumentCashFlow::money((int) $totals->income)]
            : ['income' => DocumentCashFlow::money((int) $totals->income), 'expense' => DocumentCashFlow::money((int) $totals->expense), 'net' => DocumentCashFlow::money((int) $totals->income - (int) $totals->expense)]];
    }
}
