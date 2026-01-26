<?php

namespace App\Service\Access;

use App\Http\Error\ApiException;
use App\Platform\Entity\AdminUser;
use App\Platform\Enum\Permission;
use App\Security\ApiTokenUser;

use App\Service\Access\ApiTokenScopeService;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class AccessControlService
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AuthorizationCheckerInterface $authChecker,
        private readonly ApiTokenScopeService $scopeService,
    ) {}

    public function getActor(): UserInterface
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        if (!$user instanceof UserInterface) {
            throw ApiException::authRequired('Authentication required.');
        }

        return $user;
    }

    public function isAdmin(): bool
    {
        return $this->getActor() instanceof AdminUser;
    }

    public function isToken(): bool
    {
        return $this->getActor() instanceof ApiTokenUser;
    }

    public function denyUnlessLogged(): void
    {
        $this->getActor();
    }

    public function denyUnlessAdmin(): void
    {
        if (!$this->isAdmin()) {
            throw ApiException::forbidden('Admin access required.');
        }
    }

    public function denyUnlessSuperAdmin(): void
    {
        $this->denyUnlessAdmin();

        if (!$this->authChecker->isGranted('ROLE_SUPER_ADMIN')) {
            throw ApiException::forbidden('Super admin access required.');
        }
    }

    public function denyUnlessPermission(Permission $permission): void
    {
        $actor = $this->getActor();

        if ($actor instanceof AdminUser) {
            return;
        }

        if ($actor instanceof ApiTokenUser) {
            if (!in_array($permission->value, $actor->getPermissions(), true)) {
                throw ApiException::forbidden('Missing permission: '.$permission->value, [
                    'requiredPermission' => $permission->value,
                ]);
            }
            return;
        }

        throw ApiException::authInvalid('Invalid authentication.');
    }

    public function denyUnlessDomainScopeAllowed(int $domainId): void
    {
        $actor = $this->getActor();

        if ($actor instanceof AdminUser) {
            return;
        }

        if ($actor instanceof ApiTokenUser) {
            $this->scopeService->denyUnlessDomainAllowed($actor, $domainId);
            return;
        }

        throw ApiException::authInvalid('Invalid authentication');
    }
}

?>