<?php

namespace App\DTO\Domain;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class DomainCreateDTO
{
    #[Groups(['domain:create'])]
    #[Assert\NotBlank(groups: ['domain:create'])]
    #[Assert\Length(max: 253, maxMessage: 'Name may not exceed 253 characters', groups: ['domain:create'])]
    #[Assert\Regex(
        pattern: '/^(?=.{1,253}$)(?!-)[A-Za-z0-9-]{1,63}(?<!-)(\.(?!-)[A-Za-z0-9-]{1,63}(?<!-))+$/',
        message: 'Invalid domain format.',
        groups: ['domain:create']
    )]
    public string $name;

    #[Groups(['domain:create'])]
    public ?bool $active = true;

    public function __construct(
        string $name = '',
        ?bool $active = true,
    ) {
        $this->name = $name;
        $this->active = $active;
    }
}

?>