<?php

namespace App\DTO\Token;

use App\DTO\Admin\AdminRefDTO;

use App\Platform\Entity\ApiToken;
use Symfony\Component\Serializer\Annotation\Groups;

use DateTimeImmutable;

final class TokenListItemDTO
{
    #[Groups(['token:list'])]
    public string $uuid;

    #[Groups(['token:list'])]
    public string $name;

    #[Groups(['token:list'])]
    public bool $active;

    #[Groups(['token:list'])]
    public ?DateTimeImmutable $expiresAt;

    #[Groups(['token:list'])]
    public DateTimeImmutable $createdAt;

    #[Groups(['token:list'])]
    public ?DateTimeImmutable $lastUsedAt;

    #[Groups(['token:list'])]
    public ?DateTimeImmutable $revokedAt;

    #[Groups(['token:list'])]
    public AdminRefDTO $createdBy;

    public function __construct(
        string $uuid,
        string $name,
        bool $active,
        ?DateTimeImmutable $expiresAt = null,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $lastUsedAt = null,
        ?DateTimeImmutable $revokedAt = null,
        AdminRefDTO $createdBy,
    )
    {
        $this->uuid = $uuid;
        $this->name = $name;
        $this->active = $active;
        $this->expiresAt = $expiresAt;
        $this->createdAt = $createdAt;
        $this->lastUsedAt = $lastUsedAt;
        $this->revokedAt = $revokedAt;
        $this->createdBy = $createdBy;
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
            createdBy: AdminRefDTO::fromEntity($apiToken->getCreatedByAdmin()),
        );
    }
}

?>