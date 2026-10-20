<?php

namespace App\Modules\Admin\Outlays\Controllers\Api;

use App\Core\Filters\OutlaysFilter;
use App\Http\Controllers\Controller;
use App\Modules\Admin\CashBoxes\Services\CashOperationsService;
use App\Modules\Admin\Outlays\Models\Outlay;
use App\Modules\Admin\Outlays\Requests\DeleteOutlayRequest;
use App\Modules\Admin\Outlays\Requests\ListOutlayAuditRequest;
use App\Modules\Admin\Outlays\Requests\ListOutlayReceiptsRequest;
use App\Modules\Admin\Outlays\Requests\ListOutlaysRequest;
use App\Modules\Admin\Outlays\Requests\OutlayPeriodDocumentRequest;
use App\Modules\Admin\Outlays\Requests\OutlayRequest;
use App\Modules\Admin\Outlays\Requests\UpdateOutlayAssignmentsRequest;
use App\Modules\Admin\Outlays\Requests\UpdateOutlayItemCommentsRequest;
use App\Modules\Admin\Outlays\Requests\UpdateOutlayItemStagesRequest;
use App\Modules\Admin\Outlays\Requests\UpdateOutlayStatusRequest;
use App\Modules\Admin\Outlays\Requests\UpdateOutlayWorkNotesRequest;
use App\Modules\Admin\Outlays\Services\OutlayAuditService;
use App\Modules\Admin\Outlays\Services\OutlayCounterpartyService;
use App\Modules\Admin\Outlays\Services\OutlayDocumentService;
use App\Modules\Admin\Outlays\Services\OutlayService;
use App\Services\Response\ResponseService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OutlaysController extends Controller
{
    private $service;

    public function __construct(OutlayService $outlayService)
    {
        $this->service = $outlayService;
    }

    public function index(ListOutlaysRequest $request, OutlaysFilter $filter): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', new Outlay);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'items' => $this->service->getOutlays($request, $filter),
        ]);
    }

    /**
     * Create of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function store(OutlayRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('manage', new Outlay);

        $outlay = $this->service->save($request, new Outlay);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $outlay->toArray(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Outlay $outlay): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $outlay);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $this->service->display($outlay->load([
                'groups.groupName', 'groups.items.images', 'rentals',
                'paymentCashBox.paymentMethod', 'prepaymentCashBox.paymentMethod',
            ])),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Outlay $outlay)
    {
        //
    }

    public function receipts(ListOutlayReceiptsRequest $request, CashOperationsService $service): \Illuminate\Http\JsonResponse
    {
        return ResponseService::sendJsonResponse(true, 200, [], $service->list($request, true));
    }

    public function info(Outlay $outlay, OutlayDocumentService $documents, OutlayCounterpartyService $counterparties): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $outlay);
        $outlay = $this->service->display($outlay->load(['groups.groupName', 'groups.items.images', 'subdivision', 'paymentCashBox.paymentMethod', 'prepaymentCashBox.paymentMethod']));
        try {
            $partner = $counterparties->display($counterparties->resolve($outlay));
        } catch (ValidationException $error) {
            $partner = null;
        }

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $outlay, 'counterparty' => $partner, 'documents' => $documents->catalog($outlay),
            'public_api_url' => $outlay->public_uuid ? route('api.pub.outlays.show', ['uuid' => $outlay->public_uuid]) : null,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function documents(Outlay $outlay, OutlayDocumentService $service): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $outlay);

        return ResponseService::sendJsonResponse(true, 200, [], ['items' => $service->catalog($outlay)]);
    }

    public function document(Outlay $outlay, string $type, OutlayDocumentService $service): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('view', $outlay);
        $path = $service->generate($outlay, $type);

        return $service->download($path, 'outlay-'.$outlay->id.'-'.$type.'.'.OutlayDocumentService::TYPES[$type]['format']);
    }

    public function rotatePublicLink(Outlay $outlay): \Illuminate\Http\JsonResponse
    {
        $this->authorize('manage', $outlay);
        $uuid = DB::transaction(function () use ($outlay) {
            $record = Outlay::query()->lockForUpdate()->findOrFail($outlay->id);
            $this->authorize('manage', $record);
            $record->public_uuid = (string) Str::uuid();
            $record->save();

            return $record->public_uuid;
        });

        return ResponseService::sendJsonResponse(true, 200, [], [
            'public_uuid' => $uuid, 'public_api_url' => route('api.pub.outlays.show', ['uuid' => $uuid]),
        ]);
    }

    public function periodPdf(OutlayPeriodDocumentRequest $request, OutlayDocumentService $service): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $query = Outlay::query()->whereIn('subdivision_id', $request->user()->getAvailableSubdivisionIds())
            ->where('execution_date', '>=', $request->validated('date_from'))
            ->where('execution_date', '<', CarbonImmutable::parse($request->validated('date_to'))->addDay()->format('Y-m-d'));
        if ($request->filled('subdivision_id')) {
            $query->where('subdivision_id', $request->integer('subdivision_id'));
        }
        $outlays = $query->orderBy('execution_date')->orderBy('id')->limit(101)->get();
        $positionCount = DB::table('outlay_items')->join('outlay_groups', 'outlay_groups.id', '=', 'outlay_items.outlay_group_id')
            ->whereIn('outlay_groups.outlay_id', $outlays->modelKeys())->count();
        if ($outlays->count() > 100 || $positionCount > 5000) {
            throw ValidationException::withMessages(['date_to' => 'Reduce the period: at most 100 outlays and 5000 positions may be exported.']);
        }
        $outlays->load(['groups.groupName', 'groups.items', 'subdivision']);

        return $service->download($service->periodPdf($outlays), 'outlays-'.$request->validated('date_from').'-'.$request->validated('date_to').'.pdf');
    }

    public function stages(UpdateOutlayItemStagesRequest $request, Outlay $outlay, int $item): \Illuminate\Http\JsonResponse
    {
        return ResponseService::sendJsonResponse(true, 200, [], ['item' => $this->service->updateItemStages($outlay, $item, $request->validated(), $request->user())]);
    }

    public function comments(UpdateOutlayItemCommentsRequest $request, Outlay $outlay, int $item): \Illuminate\Http\JsonResponse
    {
        return ResponseService::sendJsonResponse(true, 200, [], ['item' => $this->service->updateItemComments($outlay, $item, $request->validated(), $request->user())]);
    }

    public function audit(ListOutlayAuditRequest $request, Outlay $outlay, OutlayAuditService $service): \Illuminate\Http\JsonResponse
    {
        return ResponseService::sendJsonResponse(true, 200, [], $service->list(
            $outlay, $request->integer('page_size', 20), $request->integer('page', 1), $request->validated('event')
        ))->header('Cache-Control', 'private, no-store');
    }

    public function workNotes(UpdateOutlayWorkNotesRequest $request, Outlay $outlay): \Illuminate\Http\JsonResponse
    {
        return ResponseService::sendJsonResponse(true, 200, [], ['item' => $this->service->updateWorkNotes($outlay, $request->validated(), $request->user())]);
    }

    public function status(UpdateOutlayStatusRequest $request, Outlay $outlay): \Illuminate\Http\JsonResponse
    {
        return ResponseService::sendJsonResponse(true, 200, [], ['item' => $this->service->updateStatus($outlay, $request->integer('status_id'), $request->user())]);
    }

    public function assignments(UpdateOutlayAssignmentsRequest $request, Outlay $outlay): \Illuminate\Http\JsonResponse
    {
        return ResponseService::sendJsonResponse(true, 200, [], ['item' => $this->service->updateAssignments($outlay, $request->validated(), $request->user())]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(OutlayRequest $request, Outlay $outlay): \Illuminate\Http\JsonResponse
    {
        $this->authorize('manage', $outlay);

        $outlay = $this->service->save($request, $outlay);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $outlay->toArray(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeleteOutlayRequest $request, Outlay $outlay): \Illuminate\Http\JsonResponse
    {
        $this->authorize('manage', $outlay);

        $outlay = $this->service->delete($request, $outlay);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $outlay,
        ]);
    }
}
