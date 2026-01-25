<?php

namespace App\Http\Error;

use Symfony\Component\HttpFoundation\JsonResponse;

final class ErrorResponseFactory
{
    /**
     * @param array<string, mixed>|null $details
     */
    public function create(
        ApiErrorCode $code,
        int $status,
        string $message,
        ?array $details = null,
        ?string $requestId = null,
    ): JsonResponse
    {
        return new JsonResponse(
            data: [
                'error' => [
                    'code' => $code->value,
                    'message' => $message,
                    'details' => $details,
                    'requestId' => $requestId,
                ],
            ],
            status: $status
        );
    }
}

?>