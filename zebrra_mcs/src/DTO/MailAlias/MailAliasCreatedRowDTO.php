<?php

namespace App\DTO\MailAlias;

use Symfony\Component\Serializer\Attribute\Groups;

final class MailAliasCreatedRowDTO
{
    #[Groups(['alias:read', 'alias:create'])]
    public string $uuid;

    #[Groups(['alias:read', 'alias:create'])]
    public string $sourceEmail;

    #[Groups(['alias:read', 'alias:create'])]
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