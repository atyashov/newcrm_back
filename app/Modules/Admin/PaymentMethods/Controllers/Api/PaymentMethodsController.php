<?php

namespace App\Modules\Admin\PaymentMethods\Controllers\Api;

use App\Modules\Admin\PaymentMethods\Models\PaymentMethod;
use App\Modules\Admin\PaymentMethods\Requests\PaymentMethodsRequest;
use App\Modules\Admin\PaymentMethods\Services\PaymentMethodsService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PaymentMethodsController extends Controller
{

    private $service;

    public function __construct(PaymentMethodsService $paymentMethodsService)
    {
        $this->service = $paymentMethodsService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $this->authorize('view', (new PaymentMethod()));
        return ResponseService::sendJsonResponse(true, 200, [], [
            'items' => $this->service->getPaymentMethods()
        ]);
    }

    /**
     * Create of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(PaymentMethodsRequest $request)
    {
        $paymentMethod = $this->service->save($request, new PaymentMethod());
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $paymentMethod->toArray()
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\Admin\PaymentMethods\Models\PaymentMethod  $paymentMethod
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(PaymentMethod $paymentMethod)
    {
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $paymentMethod
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\Admin\PaymentMethods\Models\PaymentMethod  $paymentMethod
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(PaymentMethod $paymentMethod)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\Admin\PaymentMethods\Models\PaymentMethod  $paymentMethod
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(PaymentMethodsRequest $request, PaymentMethod $paymentMethod)
    {
        $paymentMethod = $this->service->save($request, $paymentMethod);
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $paymentMethod->toArray()
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\Admin\PaymentMethods\Models\PaymentMethod  $paymentMethod
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(PaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();
        return ResponseService::sendJsonResponse(true, 200, [], [
            'item' => $paymentMethod
        ]);
    }
}
