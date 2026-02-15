<?php

namespace App\DTO\MailUser;

use App\DTO\MailAlias\MailAliasReadDTO;

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

    #[Groups(['user:read'])]
    public array $aliases = [];

    public function __construct(
        string $uuid,
        string $email,
        string $domainUuid,
        bool $active,
        ?string $plainPassword = null,
        array $aliases = [],
    )
    {
        $this->uuid = $uuid;
        $this->email = $email;
        $this->domainUuid =  $domainUuid;
        $this->active = $active;
        $this->plainPassword = $plainPassword;
        $this->aliases = $aliases;
    }
}
?>