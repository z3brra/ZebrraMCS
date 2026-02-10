<?php

namespace App\DTO\MailUser;

use Symfony\Component\Serializer\Attribute\Groups;

final class MailUserReadDTO
{
    #[Groups(['user:read'])]
    public string $uuid;

    #[Groups(['user:read'])]
    public string $email;

    #[Groups(['user:read'])]
    public string $domainUuid;

    #[Groups(['user:read'])]
    public bool $active;

    #[Groups(['user:create'])]
    public ?string $plainPassword = null;

    public function __construct(
        string $uuid,
        string $email,
        string $domainUuid,
        bool $active,
        ?string $plainPassword = null,
    )
    {
        $this->uuid = $uuid;
        $this->email = $email;
        $this->domainUuid =  $domainUuid;
        $this->active = $active;
        $this->plainPassword = $plainPassword;
    }
}
?>