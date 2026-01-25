<?php

namespace App\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class LexikJwtSuccessSubscriber implements EventSubscriberInterface
{
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

        if (isset($data['token'])) {
            $wrapped['data']['token'] = $data['token'];
        }

        if (isset($data['refresh_token'])) {
            $wrapped['data']['refreshToken'] = $data['refresh_token'];
        }

        $event->setData($wrapped);
    }
}

?>