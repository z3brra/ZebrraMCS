<?php

namespace App\DTO\Token;

use Symfony\Component\Validator\Constraints as Assert;

final class TokenListQueryDTO
{
    #[Assert\Length(max: 128, maxMessage: "query may not exceed 128 characters.", groups: ['token:list'])]
    public ?string $q = null;

    public ?bool $active = null;
    public ?bool $revoked = null;
    public ?bool $expired = null;

    #[Assert\Uuid(groups: ['token:list'])]
    public ?string $createdByAdminUuid = null;

    #[Assert\Choice(
        choices: [
            'createdAt',
            'lastUsedAt',
            'expiresAt',
            'name'
        ],
        groups: ['token:list']
    )]
    public string $sort = 'createdAt';

    #[Assert\Choice(choices: ['asc', 'desc'], groups: ['token:list'])]
    public string $order = 'desc';

    // set from URL query params
    public int $page = 1;
    public int $limit = 20;
}

?>