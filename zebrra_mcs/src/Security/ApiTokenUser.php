<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

final class ApiTokenUser implements UserInterface
{
    /**
     * @param list<string> $permissions
     * @param list<int> $scopedDomainIds
     */
    public function __construct(
        private readonly string $tokenUuid,
        private readonly array $permissions,
        private readonly array $scopedDomainIds,
    ) {}

    public function getUserIdentifier(): string
    {
        return $this->tokenUuid;
    }

    public function getRoles(): array
    {
        return ['ROLE_API_TOKEN'];
    }

    public function eraseCredentials(): void
    {
        // no implement
    }

    /** @return list<string> */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /** @return list<int> */
    public function getScopedDomainIds(): array
    {
        return $this->scopedDomainIds;
    }
}

?>