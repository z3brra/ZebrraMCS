<?php

namespace App\DTO\Domain;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class DomainRenameDTO
{
    #[Groups(['domain:rename'])]
    #[Assert\NotBlank(groups: ['domain:rename'])]
    #[Assert\Length(max: 253, maxMessage: 'Name may not exceed 253 characters', groups: ['domain:rename'])]
    #[Assert\Regex(
        pattern: '/^(?=.{1,253}$)(?!-)[A-Za-z0-9-]{1,63}(?<!-)(\.(?!-)[A-Za-z0-9-]{1,63}(?<!-))+$/',
        message: 'Invalid domain format.',
        groups: ['domain:rename']
    )]
    public string $name;

    public function __construct(
        string $name,
    ) {
        $this->name = $name;
    }
}

?>