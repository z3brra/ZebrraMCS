<?php

namespace App\DTO\Domain;

use Symfony\Component\Serializer\Annotation\Groups;

final class DomainListItemDTO
{
    #[Groups(['domain:list'])]
    public string $uuid;

    #[Groups(['domain:list'])]
    public string $name;

    #[Groups(['domain:list'])]
    public bool $active;

    public function __construct(
        string $uuid,
        string $name,
        bool $active
    ) {
        $this->uuid = $uuid;
        $this->name = $name;
        $this->active = $active;
    }
}

?>