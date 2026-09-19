<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

trait AjaxResponseTrait
{
    /**
     * Return a success JSON response.
     *
     * @param  array|string  $data
     */
    protected function successResponse($data, ?string $message = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 'Success',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Return an error JSON response.
     *
     * @param  array|string|null  $data
     */
    protected function errorResponse(string $message, int $code = 500, $data = null): JsonResponse
    {
        return response()->json([
            'status' => 'Error',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function catchErrorResponse(\throwable $th, string $message = 'Error', int $code = 500): JsonResponse
    {
        $errorDetails = [
            'message' => $th->getMessage(),
            'file' => $th->getFile(),
            'line' => $th->getLine(),
            'trace' => $th->getTraceAsString(),
        ];

        return response()->json([
            'status' => 'Error',
            'error' => 'An error occurred while processing your request.',
            'details' => $errorDetails,
        ], $code);
    }
}
