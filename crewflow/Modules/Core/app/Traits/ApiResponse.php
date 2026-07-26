<?php

namespace Modules\Core\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Consistent API response shape for every module.
 * Every other module (Shifts, Dispatch, ...) uses this Trait from Core
 * so that all endpoint output stays uniform.
 */
trait ApiResponse
{
    protected function success(mixed $data = null, string $message = '', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function error(string $message, int $code = 400, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
