<?php

namespace App\DTO\MailUser;

use Symfony\Component\Validator\Constraints as Assert;

final class MailUserSearchQueryDTO
{
    #[Assert\Length(max: 254, maxMessage: 'q may not exceed 254 chars', groups: ['user:search'])]
    public ?string $q = null;

    #[Assert\Uuid(groups: ['user:search'])]
    public ?string $domainUuid = null;

    public ?bool $active = null;

    #[Assert\Choice(choices: ['email', 'domain', 'active'], groups: ['user:search'])]
    public string $sort ='email';

    #[Assert\Choice(
        choices: ['asc', 'desc'],
        message: 'The value you selected is not a valid choice. Only (email, domain or active).',
        groups: ['user:search'])
    ]
    public string $order = 'desc';

    // set from URL query params
    public int $page = 1;
    public int $limit = 20;
}

?>