<?php

namespace App\Modules\Admin\File\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\File\Requests\FileRequest;
use App\Modules\Admin\File\Services\FileService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;

class FileController extends Controller
{

    public function __construct(private readonly FileService $service)
    {

    }

    public function index(FileRequest $request): JsonResponse
    {
        return ResponseService::sendJsonResponse(true, 200, [],  $this->service->fileManager() ?? []);
    }

    public function upload(FileRequest $request): JsonResponse
    {
        $file = $this->service->upload($request);

        return ResponseService::sendJsonResponse(true, 200, [], [
            'file' => $file
        ]);
    }
}
