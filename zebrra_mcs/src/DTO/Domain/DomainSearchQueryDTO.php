<?php

namespace App\DTO\Domain;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class DomainSearchQueryDTO
{
    #[Groups(['domain:search'])]
    #[Assert\Length(max: 253, maxMessage: 'query may not exceed 253 chars', groups: ['domain:search'])]
    public ?string $q = null;

    #[Groups(['domain:search'])]
    public ?bool $active = null;

    #[Assert\Choice(
        choices: [
            'name',
            'active'
        ],
        message: "The value you selected is not a valid choice. Only (name or active).",
        groups: ['domain:search']
    )]
    public ?string $sort = null;

    #[Assert\Choice(choices: ['asc', 'desc'], groups: ['domain:search'])]
    public string $order = 'desc';

    // set from URL query params
    public int $page = 1;
    public int $limit = 20;
}


?>