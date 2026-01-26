<?php

namespace App\DTO\Token;

use App\Platform\Entity\ApiToken;
use DateTimeImmutable;
use Symfony\Component\Serializer\Annotation\Groups;

final class TokenReadDTO
{
    #[Groups(['token:read', 'token:list'])]
    public string $uuid;

    #[Groups(['token:read', 'token:list'])]
    public string $name;

    #[Groups(['token:read', 'token:list'])]
    public bool $active;

    #[Groups(['token:read', 'token:list'])]
    public ?DateTimeImmutable $expiresAt;

    #[Groups(['token:read', 'token:list'])]
    public DateTimeImmutable $createdAt;

    #[Groups(['token:read', 'token:list'])]
    public ?DateTimeImmutable $lastUsedAt;

    #[Groups(['token:read'])]
    public ?DateTimeImmutable $revokedAt;

    /**
     * @var list<string>
     */
    #[Groups(['token:read'])]
    public array $permissions = [];

    /**
     * @var list<int>
     */
    #[Groups(['token:read'])]
    public array $scopedDomainIds = [];

    public function __construct(
        string $uuid,
        string $name,
        bool $active,
        ?DateTimeImmutable $expiresAt = null,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $lastUsedAt = null,
        ?DateTimeImmutable $revokedAt = null,
        array $permissions,
        array $scopedDomainIds
    ) {
        $this->uuid = $uuid;
        $this->name = $name;
        $this->active = $active;
        $this->expiresAt = $expiresAt;
        $this->createdAt = $createdAt;
        $this->lastUsedAt = $lastUsedAt;
        $this->revokedAt = $revokedAt;
        $this->permissions = $permissions;
        $this->scopedDomainIds = $scopedDomainIds;
    }

    public static function fromEntity(ApiToken $apiToken): self
    {
        return new self(
            uuid: $apiToken->getUuid(),
            name: $apiToken->getName(),
            active: $apiToken->isActive(),
            expiresAt: $apiToken->getExpiresAt(),
            createdAt: $apiToken->getCreatedAt(),
            lastUsedAt: $apiToken->getLastUsedAt(),
            revokedAt: $apiToken->getRevokedAt(),
            permissions: $apiToken->getPermissionStrings(),
            scopedDomainIds: $apiToken->getScopedDomainIds(),
        );
    }
}

?>