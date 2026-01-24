<?php

namespace App\DTO\Admin;

use App\Platform\Entity\AdminUser;
use DateTimeImmutable;
use Symfony\Component\Serializer\Annotation\Groups;

final class AdminReadDTO
{
    #[Groups(['admin:me'])]
    public string $uuid;

    #[Groups(['admin:me'])]
    public string $email;

    /**
     * @var list<string>
     */
    #[Groups(['admin:me'])]
    public array $roles; 

    #[Groups(['admin:me'])]
    public bool $active;

    #[Groups(['admin:me'])]
    public DateTimeImmutable $createdAt;

    #[Groups(['admin:me'])]
    public ?DateTimeImmutable $updatedAt;

    public function __construct(
        string $uuid,
        string $email,
        array $roles,
        bool $active,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt = null,
    )
    {
        $this->uuid = $uuid;
        $this->email = $email;
        $this->roles = $roles;
        $this->active = $active;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromEntity(AdminUser $admin): self
    {
        return new self(
            uuid: (string) $admin->getUuid(),
            email: (string) $admin->getEmail(),
            roles: $admin->getRoles(),
            active: $admin->isActive(),
            createdAt: $admin->getCreatedAt(),
            updatedAt: $admin->getUpdatedAt(),
        );
    }
}

?>