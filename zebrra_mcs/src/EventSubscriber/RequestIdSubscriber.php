<?php

namespace App\EventSubscriber;

use Ramsey\Uuid\Uuid;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class RequestIdSubscriber implements EventSubscriberInterface
{
    public const HEADER = "X-Request-ID";
    public const ATTR = 'requestId';

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
            KernelEvents::RESPONSE => ['onKernelResponse', -100],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/v1')) {
            return;
        }

        $incoming = trim((string) $request->headers->get(self::HEADER, ''));
        $requestId = $incoming !== '' ? $incoming : Uuid::uuid7()->toString();

        $request->attributes->set(self::ATTR, $requestId);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/v1')) {
            return;
        }

        $requestId = $request->attributes->get(self::ATTR);
        if (is_string($requestId) && $requestId !== '') {
            $event->getResponse()->headers->set(self::HEADER, $requestId);
        }
    }
}

?>