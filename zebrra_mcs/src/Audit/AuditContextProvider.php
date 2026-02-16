<?php

namespace App\Audit;

use App\Platform\Entity\AdminUser;
use App\Service\RequestIdService;
use App\Security\ApiTokenUser;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

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
    public function getActor(): array
    {
        $user = $this->getCurrentUser();

        if ($user instanceof AdminUser) {
            return [
                'type' => 'admin',
                'adminUuid' => $user->getUuid(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ];
        }

        if ($user instanceof ApiTokenUser) {
            return [
                'type' => 'token',
                'tokenUuid' => $user->getUserIdentifier(),
                'roles' => $user->getRoles(),
                'permissionsCount' => count($user->getPermissions()),
                'scopesCount' => count($user->getScopedDomainUuids()),
            ];
        }

        return [
            'type' => 'anonymous',
        ];
    }

    private function getCurrentUser(): ?UserInterface
    {
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();

        return $user instanceof UserInterface ? $user : null;
    }
}

?>