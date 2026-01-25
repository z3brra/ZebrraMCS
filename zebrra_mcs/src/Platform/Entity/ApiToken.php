<?php

namespace App\Platform\Entity;

use App\Platform\Repository\ApiTokenRepository;

use DateTimeImmutable;

use Doctrine\ORM\Mapping as ORM;

use Ramsey\Uuid\Uuid;

#[ORM\Entity(repositoryClass: ApiTokenRepository::class)]
#[ORM\Table(name: 'api_tokens')]
#[ORM\UniqueConstraint(name: 'UNIQ_API_TOKENS_UUID', fields: ['uuid'])]
#[ORM\UniqueConstraint(name: 'UNIQ_API_TOKENS_TOKEN_HASH', fields: ['tokenHash'])]
#[ORM\Index(name: 'IDX_API_TOKENS_ACTIVE', columns: ['active'])]
#[ORM\Index(name: 'IDX_API_TOKENS_EXPIRES_AT', columns: ['expiresAt'])]
#[ORM\Index(name: 'IDX_API_TOKENS_LAST_USED_AT', columns: ['lastUsedAt'])]
#[ORM\HasLifecycleCallbacks]
class ApiToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 36, unique: true)]
    private string $uuid;

    #[ORM\Column(length: 128)]
    private string $name;

    #[ORM\Column(length: 64, unique: true)]
    private string $tokenHash;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $active = true;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $expiresAt = null;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $lastUsedAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $revokedAt = null;

    #[ORM\ManyToOne(targetEntity: AdminUser::class)]
    #[ORM\JoinColumn(nullable: false)]
    private AdminUser $createdByAdmin;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?self $replacedByToken = null;

    public function __construct(
        string $name,
        string $tokenHash,
        AdminUser $createdByAdmin
    ) {
        $this->uuid = Uuid::uuid7()->toString();
        $this->createdAt = new DateTimeImmutable();

        $this->name = $name;
        $this->tokenHash = $tokenHash;
        $this->createdByAdmin = $createdByAdmin;
    }

    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        // optional later : add updatedAt if necessary
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function setTokenHash(string $tokenHash): static
    {
        $this->tokenHash = $tokenHash;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active && $this->revokedAt === null && !$this->isExpired();
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt <= new DateTimeImmutable();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function touchLastUsedAt(): static
    {
        $this->lastUsedAt = new DateTimeImmutable();
        return $this;
    }

    public function getRevokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function revoke(): static
    {
        $this->revokedAt = new DateTimeImmutable();
        $this->active = false;
        return $this;
    }

    public function getCreatedByAdmin(): AdminUser
    {
        return $this->createdByAdmin;
    }

    public function getReplacedByToken(): ?self
    {
        return $this->replacedByToken;
    }

    public function setReplacedByToken(?self $replacedByToken): static
    {
        $this->replacedByToken = $replacedByToken;
        return $this;
    }
}




?>