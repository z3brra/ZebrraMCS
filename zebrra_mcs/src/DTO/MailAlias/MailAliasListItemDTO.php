<?php

namespace App\DTO\MailAlias;

use DateTimeImmutable;
use Symfony\Component\Serializer\Attribute\Groups;

final class MailAliasListItemDTO
{
    #[Groups(['alias:list'])]
    public string $sourceEmail;

    #[Groups(['alias:list'])]
    public int $destinationCount;

    #[Groups(['alias:list'])]
    public DateTimeImmutable $createdAt;

    /**
     * @var list<MailAliasDestinationItemDTO>
     */
    #[Groups(['alias:list'])]
    public array $destinations;

    public function __construct(
        string $sourceEmail,
        int $destinationCount,
        DateTimeImmutable $createdAt,
        array $destinations,
    ) {
        $this->sourceEmail = $sourceEmail;
        $this->destinationCount = $destinationCount;
        $this->createdAt = $createdAt;
        $this->destinations = $destinations;
    }

}

?>