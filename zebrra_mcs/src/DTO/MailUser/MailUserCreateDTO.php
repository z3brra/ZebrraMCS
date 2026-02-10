<?php

namespace App\DTO\MailUser;

use Symfony\Component\Validator\Constraints as Assert;

final class MailUserCreateDTO
{
    #[Assert\NotBlank(message: 'Domain uuid is required.', groups: ['user:create'])]
    #[Assert\Uuid(message: 'Domain uuid should be a valid uuid.', groups: ['user:create'])]
    public string $domainUuid;

    #[Assert\NotBlank(message: 'Email is required.', groups: ['user:create'])]
    #[Assert\Email(message: 'Email is not valid.', groups: ['user:create'])]
    #[Assert\Length(max: 255, maxMessage: 'Email may not exceed 255 characters.', groups: ['user:create'])]
    public string $email;

    #[Assert\NotBlank(message: '', groups: ['user:create'])]
    #[Assert\Length(
        min: 8,
        minMessage: 'Password should have at least 8 characters.',
        max: 255,
        maxMessage: 'Password may not exceed 255 characters.',
        groups: ['user:create']
    )]
    public string $plainPassword;

    public bool $active = true;

    public function __construct(
        string $domainUuid,
        string $email,
        string $plainPassword,
        bool $active = true,
    )
    {
        $this->domainUuid = $domainUuid;
        $this->email = $email;
        $this->plainPassword = $plainPassword;
        $this->active = $active;
    }
}

?>