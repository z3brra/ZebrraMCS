<?php

namespace App\Audit\EventSubscriber;

use App\Audit\ApiErrorAuditLogger;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiErrorAuditResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ApiErrorAuditLogger $audit,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -50]
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $response = $event->getResponse();
        $status = $response->getStatusCode();

        if ($status < 400) {
            return;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        $raw = (string) $response->getContent();

        $errorCode = null;
        $message = null;
        $details = null;

        if (str_contains($contentType, 'application/json') && $raw !== '') {
            $decoded = json_decode($raw, true);

            if (is_array($decoded) && isset($decoded['error']) && is_array($decoded['error'])) {
                $error = $decoded['error'];

                $errorCode = isset($error['code']) && is_string($error['code']) ? $error['code'] : null;
                $message = isset($error['message']) && is_string($error['message']) ? $error['message'] : null;
                $details = isset($error['details']) && is_array($error['details']) ? $error['details'] : null;
            }
        }

        // Fallback
        $errorCode ??= $this->fallbackCodeFromStatus($status);
        $message ??= $status >= 500 ? 'Internal error.' : 'HTTP error.';

        $this->audit->log(
            httpStatus: $status,
            errorCode: $errorCode,
            message: $message,
            details: $details,
        );
    }

    private function fallbackCodeFromStatus(int $status): string
    {
        return match ($status) {
            400 => 'BAD_REQUEST',
            401 => 'AUTH_REQUIRED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            409 => 'CONFLICT',
            422 => 'VALIDATION_ERROR',
            429 => 'RATE_LIMITED',
            default => 'INTERNAL_ERROR',
        };
    }
}

?>