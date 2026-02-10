<?php

namespace App\DTO\MailUser;

use DateTimeImmutable;
use Symfony\Component\Serializer\Attribute\Groups;

final class MailUserListItemDTO
{
    #[Groups(['user:list', 'user:search'])]
    public string $uuid;

    #[Groups(['user:list', 'user:search'])]
    public string $email;

    #[Groups(['user:list', 'user:search'])]
    public string $domainUuid;

    #[Groups(['user:list', 'user:search'])]
    public bool $active;

    public function __construct(
        string $uuid,
        string $email,
        string $domainUuid,
        bool $active,
    ) {
        $this->uuid = $uuid;
        $this->email = $email;
        $this->domainUuid = $domainUuid;
        $this->active = $active;
    }
}

?>