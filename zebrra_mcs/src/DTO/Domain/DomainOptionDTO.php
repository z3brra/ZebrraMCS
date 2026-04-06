<?php

namespace App\DTO\Domain;

use Symfony\Component\Serializer\Attribute\Groups;

final class DomainOptionDTO
{
    #[Groups(['domain:option'])]
    public string $uuid;

    #[Groups(['domain:option'])]
    public string $name;

    public function __construct(string $uuid, string $name)
    {
        $this->uuid = $uuid;
        $this->name = $name;
    }
}

?>