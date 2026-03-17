<?php

namespace App\EventSubscriber;

use App\Platform\Entity\AdminUser;
use App\Service\Auth\AdminRefreshTokenService;
use App\Service\Auth\RefreshTokenCookieService;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class LexikJwtSuccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AdminRefreshTokenService $refreshTokenService,
        private readonly RefreshTokenCookieService $cookieService,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();

        $wrapped = [
            'data' => [],
        ];

        if (isset($data['token']) && is_string($data['token'])) {
            $wrapped['data']['token'] = $data['token'];
        }

        $user = $event->getUser();
        if ($user instanceof AdminUser) {
            $plainRefresh = $this->refreshTokenService->issue($user);

            $response = $event->getResponse();
            $response->headers->setCookie($this->cookieService->createCookie($plainRefresh));

            $wrapped['data']['refreshTokenIssued'] = true;
        } else {
            $wrapped['data']['refreshTokenIssued'] = false;
        }

        $event->setData($wrapped);
    }
}

?>