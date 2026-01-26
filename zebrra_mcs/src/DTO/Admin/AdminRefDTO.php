<?php

namespace App\DTO\Admin;

use App\Platform\Entity\AdminUser;
use Symfony\Component\Serializer\Annotation\Groups;

final class AdminRefDTO
{
    #[Groups(['token:list', 'token:read'])]
    public string $uuid;

    #[Groups(['token:list', 'token:read'])]
    public string $email;

    public function __construct(
        string $uuid,
        string $email,
    ) {
        $this->uuid = $uuid;
        $this->email = $email;
    }

    public static function fromEntity(AdminUser $admin): self
    {
        return new self(
            uuid: $admin->getUuid(),
            email: $admin->getEmail()
        );
    }
}

?>