<?php

namespace App\DTO\MailAlias;

use Symfony\Component\Validator\Constraints as Assert;

final class MailAliasSearchQueryDTO
{
    #[Assert\Length(max: 320, groups: ['alias:search'])]
    public ?string $q = null;

    #[Assert\Choice(
        choices: ['source', 'destinationsCount', 'createdAt'],
        message: "The value you selected is not a valid choice. Only (source, destinationCount, createdAt) allowed.",
        groups: ['alias:search']
    )]
    public ?string $sort = null;

    #[Assert\Choice(choices: ['asc', 'desc'], groups: ['alias:search'])]
    public string $order = 'asc';

    public int $page = 1;
    public int $limit = 20;
}

?>