<?php

namespace App\Modules\Pub\Outlays\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Outlays\Models\Outlay;
use App\Modules\Pub\Outlays\Services\PublicOutlayService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;

class OutlaysController extends Controller
{
    public function show(string $uuid, PublicOutlayService $service): JsonResponse
    {
        $outlay = Outlay::query()->where('public_uuid', $uuid)->firstOrFail();

        return ResponseService::sendJsonResponse(true, 200, [], ['item' => $service->data($outlay)])
            ->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
