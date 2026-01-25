<?php

namespace App\EventSubscriber;

use App\Http\Error\{
    ApiErrorCode,
    ErrorResponseFactory
};

use App\Service\RequestIdService;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class LexikJwtErrorSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ErrorResponseFactory $factory,
        private readonly RequestIdService $requestIdService,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_FAILURE => 'onAuthenticationFailure',
            Events::JWT_NOT_FOUND => 'onJwtNotFound',
            Events::JWT_INVALID => 'onJwtInvalid',
            Events::JWT_EXPIRED => 'onJwtExpired',
        ];
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/v1')) {
            return;
        }

        $event->setResponse(
            $this->factory->create(
                code: ApiErrorCode::AUTH_INVALID,
                status: 401,
                message: 'Invalid credentials.',
                details: null,
                requestId: $this->requestIdService->get(),
            )
        );
    }

    public function onJwtNotFound(JWTNotFoundEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/v1')) {
            return;
        }

        $event->setResponse(
            $this->factory->create(
                code: ApiErrorCode::AUTH_REQUIRED,
                status: 401,
                message: 'Authentication required.',
                details: null,
                requestId: $this->requestIdService->get(),
            )
        );
    }

    public function onJwtInvalid(JWTInvalidEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/v1')) {
            return;
        }

        $event->setResponse(
            $this->factory->create(
                code: ApiErrorCode::AUTH_INVALID,
                status: 401,
                message: 'Invalid token.',
                details: null,
                requestId: $this->requestIdService->get(),
            )
        );
    }

    public function onJwtExpired(JWTExpiredEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/v1')) {
            return;
        }

        $event->setResponse(
            $this->factory->create(
                code: ApiErrorCode::AUTH_INVALID,
                status: 401,
                message: 'Token expired.',
                details: null,
                requestId: $this->requestIdService->get(),
            )
        );
    }
}

?>