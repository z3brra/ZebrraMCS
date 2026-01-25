<?php

namespace App\Service;

use App\EventSubscriber\RequestIdSubscriber;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestIdService
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {}

    public function get(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }

        $id = $request->attributes->get(RequestIdSubscriber::ATTR);

        return is_string($id) && $id !== '' ? $id : null;
    }
}

?>