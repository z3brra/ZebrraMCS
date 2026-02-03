<?php

namespace App\Platform\Entity;

use App\Platform\Repository\ApiTokenRepository;
use App\Platform\Enum\Permission;

use DateTimeImmutable;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Ramsey\Uuid\Uuid;

#[ORM\Entity(repositoryClass: ApiTokenRepository::class)]
#[ORM\Table(name: 'api_tokens')]
#[ORM\UniqueConstraint(name: 'UNIQ_API_TOKENS_UUID', fields: ['uuid'])]
#[ORM\UniqueConstraint(name: 'UNIQ_API_TOKENS_TOKEN_HASH', fields: ['tokenHash'])]
#[ORM\Index(name: 'IDX_API_TOKENS_ACTIVE', columns: ['active'])]
#[ORM\Index(name: 'IDX_API_TOKENS_EXPIRES_AT', columns: ['expiresAt'])]
#[ORM\Index(name: 'IDX_API_TOKENS_LAST_USED_AT', columns: ['lastUsedAt'])]
#[ORM\HasLifecycleCallbacks]
final class ApiToken
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

    /**
     * @var Collection<int, ApiTokenPermission>
     */
    #[ORM\OneToMany(mappedBy: 'token', targetEntity: ApiTokenPermission::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $permissions;

    /**
     * @var Collection<int, ApiTokenScope>
     */
    #[ORM\OneToMany(mappedBy: 'token', targetEntity: ApiTokenScope::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $scopes;

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

        $this->permissions = new ArrayCollection();
        $this->scopes = new ArrayCollection();
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

    /**
     * @return Collection<int, ApiTokenPermission>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    /**
     * @return list<string>
     */
    public function getPermissionStrings(): array
    {
        $out = [];
        foreach ($this->permissions as $permission) {
            $out[] = $permission->getPermission();
        }
        return array_values(array_unique($out));
    }

    public function hasPermission(Permission|string $permission): bool
    {
        $needle = $permission instanceof Permission ? $permission->value : $permission;
        foreach ($this->permissions as $perm) {
            if ($perm->getPermission() === $needle) {
                return true;
            }
        }
        return false;
    }

    public function addPermissionString(Permission|string $permission): static
    {
        $value = $permission instanceof Permission ? $permission->value : $permission;

        foreach ($this->permissions as $perm) {
            if ($perm->getPermission() === $value) {
                return $this;
            }
        }

        $this->permissions->add(new ApiTokenPermission($this, $value));
        return $this;
    }

    public function clearPermissions(): static
    {
        $this->permissions->clear();
        return $this;
    }

    /**
     * @return Collection<int, ApiTokenScope>
     */
    public function getScopes(): Collection
    {
        return $this->scopes;
    }

    /**
     * @return list<string>
     */
    public function getScopedDomainUuids(): array
    {
        $out = [];
        foreach ($this->scopes as $scope) {
            $out[] = $scope->getDomainUuid();
        }
        $out = array_values(array_unique($out));
        sort($out);
        return $out;
    }

    public function hasScope(string $domainUuid): bool
    {
        $domainUuid = trim($domainUuid);
        foreach ($this->scopes as $scope) {
            if ($scope->getDomainUuid() === $domainUuid) {
                return true;
            }
        }
        return false;
    }

    public function addScopeUuid(string $domainUuid): static
    {
        $domainUuid = trim($domainUuid);
        if ($domainUuid === '') {
            return $this;
        }
        if ($this->hasScope($domainUuid)) {
            return $this;
        }
        $this->scopes->add(new ApiTokenScope($this, $domainUuid));
        return $this;
    }

    public function clearScopes(): static
    {
        $this->scopes->clear();
        return $this;
    }
}

?>