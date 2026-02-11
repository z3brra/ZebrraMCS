<?php

namespace App\DTO\MailAlias;

use Symfony\Component\Validator\Constraints as Assert;

final class MailAliasCreateDTO
{
    #[Assert\NotBlank(message: 'Source email is required', groups: ['alias:create'])]
    #[Assert\Email(message: 'Source email must be a valid email', groups: ['alias:create'])]
    public string $sourceEmail;

    /**
     * @var list<string>
     */
    #[Assert\NotBlank(message: 'Destinations is required.', groups: ['alias:create'])]
    #[Assert\Count(min: 1, minMessage: 'Destinations must contain at lest 1 email', groups: ['alias:create'])]
    #[Assert\All(
        constraints: [
            new Assert\NotBlank(message: 'destination cannot be blank.'),
            new Assert\Email(message: 'destination must be a valid email.')
        ],
        groups: ['alias:create']
    )]
    public array $destinations = [];

    public function __construct(
        string $sourceEmail,
        array $destinations,
    ) {
        $this->sourceEmail = $sourceEmail;
        $this->destinations = $destinations;
    }
}


?>