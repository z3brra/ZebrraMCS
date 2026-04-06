<?php

namespace App\Platform\Entity;

use App\Platform\Repository\AdminRefreshTokenRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminRefreshTokenRepository::class)]
#[ORM\Table(name: 'admin_refresh_tokens')]
#[ORM\Index(name: 'IDX_ART_EXPIRES', columns: ['expires_at'])]
#[ORM\Index(name: 'IDX_ART_REVOKED', columns: ['revoked_at'])]
#[ORM\UniqueConstraint(name: 'UNIQ_ART_TOKEN_HASH', columns: ['token_hash'])]
class AdminRefreshToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue()]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AdminUser::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AdminUser $adminUser;

    #[ORM\Column(name: 'token_hash', length: 64, unique: true)]
    private string $tokenHash;

    #[ORM\Column(name: 'expires_at', type: 'datetime_immutable')]
    private DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'revoked_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $revokedAt = null;

    #[ORM\Column(name: 'ip', length: 64, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(name: 'user_agent', length: 255, nullable: true)]
    private ?string $userAgent = null;

    public function __construct(
        AdminUser $adminUser,
        string $tokenHash,
        DateTimeImmutable $expiresAt,
        ?string $ip = null,
        ?string $userAgent = null,
    )
    {
        $this->adminUser = $adminUser;
        $this->tokenHash = $tokenHash;
        $this->expiresAt = $expiresAt;
        $this->createdAt = new DateTimeImmutable();
        $this->ip = $ip;
        $this->userAgent = $userAgent;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdminUser(): AdminUser
    {
        return $this->adminUser;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= new DateTimeImmutable();
    }

    public function getRevokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function isActive(): bool
    {
        return $this->revokedAt === null && !$this->isExpired();
    }

    public function revoke(): void
    {
        if ($this->revokedAt === null) {
            $this->revokedAt = new DateTimeImmutable();
        }
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }
}

?>