<?php

namespace App\EventSubscriber;

use App\Http\Error\ApiException;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LoginPayloadValidationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 50],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->getMethod() !== 'POST') {
            return;
        }

        if ($request->getPathInfo() !== '/api/v1/auth/login') {
            return;
        }

        $contentType = $request->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'application/json')) {
            throw ApiException::badRequest('Validation error', [
                'violations' => [
                    [
                        'propery' => 'Content-Type',
                        'message' => 'Content-Type must be application/json.',
                        'code' => null,
                    ],
                ],
            ]);
        }

        $raw = $request->getContent();
        $data = json_decode($raw ?: '', true);

        if (!is_array($data)) {
            throw ApiException::badRequest('Validation error', [
                'violations' => [
                    [
                        'property' => 'body',
                        'message' => 'Invalid JSON body.',
                        'code' => null,
                    ],
                ],
            ]);
        }

        $missing = [];
        foreach (['email', 'password'] as $field) {
            if (!isset($data[$field]) || !is_string($data[$field]) || trim($data[$field]) === '') {
                $missing[] = $field;
            }
        }

        if ($missing !== []) {
            throw ApiException::validation('Validation error', [
                'violations' => array_map(
                    static fn (string $f) => [
                        'property' => $f,
                        'message' => 'This field is required.',
                        'code' => null,
                    ],
                    $missing
                ),
            ]);
        }
    }
}

?>