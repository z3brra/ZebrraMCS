<?php

namespace App\DTO\Admin;

use Symfony\Component\Validator\Constraints as Assert;

final class AdminCreateDTO
{
    #[Assert\NotBlank(message: 'Email is required.', groups: ['admin:create'])]
    #[Assert\Email(message: 'Email is not valid.', groups: ['admin:create'])]
    #[Assert\Length(max: 255, maxMessage: 'Email may not exceed 255 characters.', groups: ['admin:create'])]
    public string $email;

    #[Assert\NotBlank(message: 'Password is required.', groups: ['admin:create'])]
    #[Assert\Length(
        min: 8,
        minMessage: 'Password should have at least 8 characters.',
        max: 255,
        maxMessage: 'Password may not exceed 255 characters.',
        groups: ['admin:create']
    )]
    public string $plainPassword;

    public array $roles = [];

    public bool $active = true;

    public bool $createMailUser = false;

    public function __construct(
        string $email,
        string $plainPassword,
        array $roles = [],
        bool $active = true,
        bool $createMailUser = false,
    ) {
        $this->email = $email;
        $this->plainPassword = $plainPassword;
        $this->roles = $roles;
        $this->active = $active;
        $this->createMailUser = $createMailUser;
    }
}

?>