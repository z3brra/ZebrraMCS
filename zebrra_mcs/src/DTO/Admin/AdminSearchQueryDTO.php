<?php

namespace App\DTO\Admin;

use Symfony\Component\Validator\Constraints as Assert;

final class AdminSearchQueryDTO
{
    #[Assert\Length(max: 180, groups: ['admin:search'])]
    public ?string $q = null;

    public ?bool $active = null;

    public ?bool $deleted = null;

    public ?bool $hasMailbox = null;

    #[Assert\Choice(
        choices: ['email', 'createdAt', 'active'],
        groups: ['admin:search']
    )]
    public ?string $sort = null;

    #[Assert\Choice(
        choices: ['asc', 'desc'],
        groups: ['admin:search']
    )]
    public string $order = 'desc';

    public int $page = 1;
    public int $limit = 20;
}

?>