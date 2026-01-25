<?php

namespace App\EventSubscriber;

use App\Http\Error\{
    ApiErrorCode,
    ApiException,
    ErrorResponseFactory
};

use App\Service\RequestIdService;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\{
    AccessDeniedHttpException,
    HttpExceptionInterface,
    NotFoundHttpException
};
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ErrorResponseFactory $factory,
        private readonly RequestIdService $requestIdService,
        private readonly bool $debug
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $requsest = $event->getRequest();

        if (!str_starts_with($requsest->getPathInfo(), '/api/')) {
            return;
        }

        $requestId = $this->requestIdService->get();
        $e = $event->getThrowable();

        if ($e instanceof ApiException) {
            $event->setResponse(
                $this->factory->create(
                    code: $e->errorCode,
                    status: $e->httpStatus,
                    message: $e->getMessage(),
                    details: $e->details,
                    requestId: $requestId,
                )
            );
            return;
        }

        // Validator failures -> 422 (fallback)
        if ($e instanceof ValidationFailedException) {
            $details = [
                'violations' => array_map(
                    static fn($v) => [
                        'property' => $v->getPropertyPath(),
                        'message' => $v->getMessage(),
                        'code' => $v->getCode()
                    ],
                    iterator_to_array($e->getViolations())
                ),
            ];

            $event->setResponse(
                $this->factory->create(
                    code: ApiErrorCode::VALIDATION_ERROR,
                    status: 422,
                    message: 'Validation error',
                    details: $details,
                    requestId: $requestId,
                )
            );
            return;
        }

        // Security exceptions (generic)
        if ($e instanceof AuthenticationException) {
            $event->setResponse(
                $this->factory->create(
                    code: ApiErrorCode::AUTH_INVALID,
                    status: 401,
                    message: 'Authentication failed',
                    details: null,
                    requestId: $requestId,
                )
            );
            return;
        }

        if ($e instanceof AccessDeniedException || $e instanceof AccessDeniedHttpException) {
            $event->setResponse(
                $this->factory->create(
                    code: ApiErrorCode::FORBIDDEN,
                    status: 403,
                    message: $e->getMessage() ?: 'Forbidden',
                    details: null,
                    requestId: $requestId,
                )
            );
            return;
        }

        // Not found -> 404
        if ($e instanceof NotFoundHttpException) {
            $event->setResponse(
                $this->factory->create(
                    code: ApiErrorCode::NOT_FOUND,
                    status: 404,
                    message: $e->getMessage() ?: 'Not found',
                    details: null,
                    requestId: $requestId,
                )
            );
            return;
        }

        // Other HttpException
        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();

            $mapped = match ($status) {
                401 => [ApiErrorCode::AUTH_REQUIRED, 401],
                403 => [ApiErrorCode::FORBIDDEN, 403],
                404 => [ApiErrorCode::NOT_FOUND, 404],
                409 => [ApiErrorCode::CONFLICT, 409],
                422 => [ApiErrorCode::VALIDATION_ERROR, 422],
                429 => [ApiErrorCode::RATE_LIMITED, 429],
                default => [ApiErrorCode::INTERNAL_ERROR, 500],
            };

            [$code, $finalStatus] = $mapped;

            $message = ($finalStatus === 500)
                ? 'Internal error'
                : ($e->getMessage() ?: $code->value);
            
            $event->setResponse(
                $this->factory->create(
                    code: $code,
                    status: $finalStatus,
                    message: $message,
                    details: null,
                    requestId: $requestId,
                )
            );
            return;
        }

        // Fallback -> 500
        $details = null;
        if ($this->debug) {
            $details = [
                'exception' => get_class($e),
            ];
        }

        $event->setResponse(
            $this->factory->create(
                code: ApiErrorCode::INTERNAL_ERROR,
                status: 500,
                message: 'Internal error',
                details: $details,
                requestId: $requestId,
            )
        );
    }
}

?>