<?php

namespace App\Services\Response;

class ResponseService
{
    private static function ResponseParams($status, $errors = [], $data = []): array
    {
        return [
            'status' => $status,
            'errors' => (object)$errors,
            'data' => (object)$data,
        ];
    }

    public static function sendJsonResponse($status, $code = 200, $errors = [], $data = []): \Illuminate\Http\JsonResponse
    {
        return response()->json(

            self::responseParams($status, $errors, $data),
            $code
        );
    }

    public static function success($data = []): \Illuminate\Http\JsonResponse
    {
        return self::sendJsonResponse(true, 200, [], []);
    }

    public static function notFound($data = []): \Illuminate\Http\JsonResponse
    {
        return self::sendJsonResponse(false, 404, [], []);
    }
}
