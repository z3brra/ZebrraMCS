<?php

namespace App\DTO\Admin;

use Symfony\Component\Serializer\Attribute\Groups;

final class AdminPasswordResetResponseDTO
{
    #[Groups(['admin:secret'])]
    public string $adminUuid;

    #[Groups(['admin:secret'])]
    public string $email;

    #[Groups(['admin:secret'])]
    public string $newPassword;

    public function __construct(
        string $adminUuid,
        string $email,
        string $newPassword
    ) {
        $this->adminUuid = $adminUuid;
        $this->email = $email;
        $this->newPassword = $newPassword;
    }
}