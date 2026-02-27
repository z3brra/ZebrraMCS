<?php

namespace App\DTO\MailAlias;

use Symfony\Component\Serializer\Attribute\Groups;

final class MailAliasReadDTO
{
    #[Groups(['alias:read', 'user:read'])]
    public string $uuid;

    #[Groups(['alias:read', 'user:read'])]
    public string $sourceEmail;

    #[Groups(['alias:read', 'user:read'])]
    public string $destinationEmail;

    public function __construct(
        string $uuid,
        string $sourceEmail,
        string $destinationEmail
    ) {
        $this->uuid = $uuid;
        $this->sourceEmail = $sourceEmail;
        $this->destinationEmail = $destinationEmail;
    }
}

?>