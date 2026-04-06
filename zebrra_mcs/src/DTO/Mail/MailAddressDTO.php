<?php

namespace App\DTO\Mail;

use Symfony\Component\Validator\Constraints as Assert;

final class MailAddressDTO
{
    #[Assert\NotBlank(groups: ['mail:send'])]
    #[Assert\Email(groups: ['mail:send'])]
    public string $email;

    #[Assert\Length(max: 255, groups: ['mail:send'])]
    public ?string $name = null;
}

?>