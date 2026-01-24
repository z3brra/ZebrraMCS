<?php

namespace App\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class LoginRateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RateLimiterFactory $loginLimiter,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 30],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        if ($request->getMethod() !== 'POST') {
            return;
        }

        if ($request->getPathInfo() !== '/api/v1/auth/login') {
            return;
        }

        $ip = $request->getClientIp() ?? 'unknown';
        $email = 'unknown';
        $content = $request->getContent();
        if (is_string($content) && $content !== '') {
            $data = json_decode($content, true);
            if (is_array($data) && isset($data['email']) && is_string($data['email'])) {
                $email = strtolower(trim($data['email']));
            }
        }

        $key = sprintf('login:%s:%s', $ip, hash('sha256', $email));

        $limiter = $this->loginLimiter->create($key);
        $limit = $limiter->consume(1);

        if ($limit->isAccepted()) {
            return;
        }

        $retryAfter = $limit->getRetryAfter();
        $retrySeconds = $retryAfter ? max(1, $retryAfter->getTimestamp() -  time()) : 60;

        $response = new JsonResponse(
            data: [
                "error" => [
                    "code" => "rate_limited",
                    "message" => "Too many login attempts. Please retry later.",
                    "details" => [
                        "retryAfterSeconds" => $retrySeconds,
                    ]
                ],
            ],
            status: JsonResponse::HTTP_TOO_MANY_REQUESTS
        );
        $response->headers->set('Retry-After', (string) $retrySeconds);

        $event->setResponse($response);
    }
}

?>