<?php

namespace App\DTO\MailAlias;

use DateTimeImmutable;
use Symfony\Component\Serializer\Attribute\Groups;

final class MailAliasDestinationItemDTO
{
    #[Groups(['alias:list'])]
    public string $uuid;

    #[Groups(['alias:list'])]
    public string $destinationEmail;

    #[Groups(['alias:list'])]
    public DateTimeImmutable $createdAt;

    public function __construct(
        string $uuid,
        string $destinationEmail,
        DateTimeImmutable $createdAt,
    ) {
        $this->uuid = $uuid;
        $this->destinationEmail = $destinationEmail;
        $this->createdAt = $createdAt;
    }
}

?>