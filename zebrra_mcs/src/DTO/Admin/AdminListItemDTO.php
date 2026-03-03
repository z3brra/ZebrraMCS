<?php

namespace App\DTO\Admin;

use DateTimeImmutable;
use Symfony\Component\Serializer\Attribute\Groups;

final class AdminListItemDTO
{
    #[Groups(['admin:list'])]
    public string $uuid;

    #[Groups(['admin:list'])]
    public string $email;

    #[Groups(['admin:list'])]
    public array $roles;

    #[Groups(['admin:list'])]
    public bool $active;

    #[Groups(['admin:list'])]
    public bool $isDeleted;

    #[Groups(['admin:list'])]
    public bool $hasMailbox;

    #[Groups(['admin:list'])]
    public DateTimeImmutable $createdAt;

    public function __construct(
        string $uuid,
        string $email,
        array $roles,
        bool $active,
        bool $isDeleted,
        bool $hasMailbox,
        DateTimeImmutable $createdAt,
    ) {
        $this->uuid = $uuid;
        $this->email = $email;
        $this->roles = $roles;
        $this->active = $active;
        $this->isDeleted = $isDeleted;
        $this->hasMailbox = $hasMailbox;
        $this->createdAt = $createdAt;
    }
}

?>