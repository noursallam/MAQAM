<?php

namespace App\Support;

use App\Exceptions\ApiException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Renders every API failure in the same `{ success, message, errors, error_code }` envelope.
 */
class ApiExceptionRenderer
{
    public static function render(Throwable $e): JsonResponse
    {
        [$status, $code, $message, $errors, $headers] = match (true) {
            $e instanceof ApiException => [$e->status, $e->errorCode, $e->getMessage(), $e->errors, []],
            $e instanceof ValidationException => [422, 'VALIDATION_ERROR', __('api.validation_failed'), $e->errors(), []],
            $e instanceof AuthenticationException => [401, 'UNAUTHENTICATED', __('api.unauthenticated'), [], []],
            $e instanceof AuthorizationException => [403, 'FORBIDDEN', __('api.forbidden'), [], []],
            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException => [404, 'NOT_FOUND', __('api.not_found'), [], []],
            $e instanceof ThrottleRequestsException => [429, 'TOO_MANY_REQUESTS', __('api.too_many_requests'), [], $e->getHeaders()],
            $e instanceof HttpExceptionInterface => [$e->getStatusCode(), 'HTTP_ERROR', __('api.request_failed'), [], $e->getHeaders()],
            default => [500, 'SERVER_ERROR', __('api.server_error'), [], []],
        };

        $body = [
            'success' => false,
            'message' => $message,
            'errors' => (object) $errors,
            'error_code' => $code,
        ];

        // Internal details never leave the server outside local debugging
        if ($status === 500 && config('app.debug')) {
            $body['debug'] = ['exception' => $e::class, 'message' => $e->getMessage()];
        }

        return response()->json($body, $status, $headers);
    }
}
