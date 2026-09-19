<?php

namespace App\Dashboard\Http\Traits;

use Illuminate\Http\JsonResponse;

trait AjaxResponseTrait
{
    protected function successResponse(mixed $data, ?string $message = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 'Success',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function errorResponse(string $message, int $code = 500, mixed $data = null): JsonResponse
    {
        return response()->json([
            'status' => 'Error',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function catchErrorResponse(\Throwable $throwable, string $message = 'Error', int $code = 500): JsonResponse
    {
        return response()->json([
            'status' => 'Error',
            'error' => 'An error occurred while processing your request.',
            'details' => [
                'message' => $throwable->getMessage(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'trace' => $throwable->getTraceAsString(),
            ],
        ], $code);
    }
}
