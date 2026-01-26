<?php

namespace App\Audit\EventSubscriber;

use App\Audit\ApiErrorAuditLogger;
use App\Http\Error\{
    ApiException,
    ApiErrorCode
};

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;


final class ApiErrorAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ApiErrorAuditLogger $audit,
        private readonly bool $kernelDebug,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 20],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $error = $event->getThrowable();

        [$status, $code, $message, $details] = $this->mapThrowable($error);

        $this->audit->log(
            httpStatus: $status,
            errorCode: $code,
            message: $message,
            details: $details
        );
    }

    /**
     * @return array{0:int, 1:string, 2:string, 3:array<string, mixed>|null}
     */
    private function mapThrowable(\Throwable $error): array
    {
        if ($error instanceof ApiException) {
            return [
                $error->httpStatus,
                $error->errorCode->name,
                $error->getMessage(),
                $this->sanitizeDetails($error->details),
            ];
        }

        if ($error instanceof AuthenticationException) {
            return [
                401,
                ApiErrorCode::AUTH_INVALID->name,
                'Invalid authentication.',
                $this->devDetails($error)
            ];
        }

        if ($error instanceof AccessDeniedException) {
            return [
                403,
                ApiErrorCode::FORBIDDEN->name,
                'Forbidden.',
                $this->devDetails($error)
            ];
        }

        if ($error instanceof TooManyRequestsHttpException) {
            return [
                429,
                ApiErrorCode::RATE_LIMITED->name,
                'Too many requests.',
                $this->devDetails($error)
            ];
        }

        if ($error instanceof BadRequestHttpException) {
            return [
                400,
                ApiErrorCode::BAD_REQUEST->name,
                'Bad request.',
                $this->devDetails($error)
            ];
        }

        if ($error instanceof HttpExceptionInterface) {
            $status = $error->getStatusCode();
            $mapped = match ($status) {
                400 => ApiErrorCode::BAD_REQUEST->name,
                401 => ApiErrorCode::AUTH_REQUIRED->name,
                403 => ApiErrorCode::FORBIDDEN->name,
                404 => ApiErrorCode::NOT_FOUND->name,
                409 => ApiErrorCode::CONFLICT->name,
                422 => ApiErrorCode::VALIDATION_ERROR->name,
                429 => ApiErrorCode::RATE_LIMITED->name,
                default => ApiErrorCode::INTERNAL_ERROR->name,
            };

            $safeMessage = $status >= 500 ? 'Internal error.' : ($error->getMessage() ?: 'HTTP error.');

            return [$status, $mapped, $safeMessage, $this->devDetails($error)];
        }

        // Fallback
        return [
            500,
            ApiErrorCode::INTERNAL_ERROR->name,
            'Internal error.',
            $this->devDetails($error)
        ];
    }

    /**
     * @param array<string, mixed>|null $details
     * @return array<string, mixed>|null
     */
    private function sanitizeDetails(?array $details): ?array
    {
        if ($details === null) {
            return null;
        }

        $blockedKeys = [
            'password',
            'plainPassword',
            'token',
            'access_token',
            'authorization',
            'secret'
        ];

        $clean = $details;

        array_walk_recursive($clean, function (&$value, $key) use ($blockedKeys) {
            if (is_string($key) && in_array(strtolower((string) $key), $blockedKeys, true)) {
                $value = '[REDACTED]';
            }
        });

        return $clean;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function devDetails(\Throwable $error): ?array
    {
        if (!$this->kernelDebug) {
            return ['exceptionClass' => $error::class];
        }

        return [
            'exceptionClass' => $error::class,
            'exceptionMessage' => $error->getMessage(),
            'trace' => array_slice(explode("\n", $error->getTraceAsString()), 0, 20),
        ];
    }
}

?>