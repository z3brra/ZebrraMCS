<?php

namespace App\DTO\Mail;

use Symfony\Component\Validator\Constraints as Assert;

final class MailHeaderDTO
{
    #[Assert\NotBlank(groups: ['mail:send'])]
    #[Assert\Length(max: 255, groups: ['mail:send'])]
    public string $name;

    #[Assert\NotBlank(groups: ['mail:send'])]
    #[Assert\Length(max: 1000, groups: ['mail:send'])]
    public string $value;
}

?>