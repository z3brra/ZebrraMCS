<?php

namespace App\Service\Access;

use App\Platform\Entity\AdminUser;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class AccessControlService
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private AuthorizationCheckerInterface $authChecker,
    ) {}

    public function getAdmin(): AdminUser
    {
        $user = $this->getInternalUser();
        if (!$user instanceof AdminUser) {
            throw new AccessDeniedHttpException("Admin is not authenticated");
        }
        return $user;
    }

    public function isLogged(): bool
    {
        return $this->getInternalUser() instanceof AdminUser;
    }

    public function denyUnlessLogged(): void
    {
        if (!$this->isLogged()) {
            throw new AccessDeniedHttpException("Admin is not authenticated");
        }
    }

    public function denyUnlessSuperAdmin(): void
    {
        if (!$this->authChecker->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedHttpException('Super-admin reserved access');
        }
    }

    /** @internal */
    private function getInternalUser(): ?UserInterface
    {
        return $this->tokenStorage->getToken()?->getUser();
    }
}

?>