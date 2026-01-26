<?php

namespace App\Audit;

use App\Platform\Entity\AdminUser;
use App\Service\RequestIdService;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class AuditContextProvider
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly RequestIdService $requestIdService,
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    public function getRequestId(): string
    {
        return $this->requestIdService->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        $request = $this->requestStack->getCurrentRequest();

        return [
            'ip' => $request?->getClientIp(),
            'userAgent' => $request?->headers->get('User-Agent'),
            'method' => $request?->getMethod(),
            'path' => $request?->getPathInfo(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getActorAdmin(): array
    {
        $user = $this->tokenStorage->getToken()->getUser();

        if (!$user instanceof AdminUser) {
            return [
                'type' => 'admin',
                'adminUuid' => null,
                'email' => null,
                'roles' => [],
            ];
        }

        return [
            'type' => 'admin',
            'adminUuid' => $user->getUuid(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ];
    }
}

?>