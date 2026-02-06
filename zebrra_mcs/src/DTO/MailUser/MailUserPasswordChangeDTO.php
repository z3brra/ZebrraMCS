<?php

namespace App\DTO\MailUser;

use Symfony\Component\Validator\Constraints as Assert;

final class MailUserPasswordChangeDTO
{
    #[Assert\NotBlank(message: 'Old password is required', groups: ['user:password'])]
    #[Assert\Length(
        min: 8,
        minMessage: 'Old password should have at least 8 characters',
        max: 255,
        maxMessage: 'Old password may not exceed 255 characters',
        groups: ['user:password']
    )]
    public string $oldPassword;

    #[Assert\NotBlank(message: 'New password is required', groups: ['user:password'])]
    #[Assert\Length(
        min: 8,
        minMessage: 'New password should have at least 8 characters',
        max: 255,
        maxMessage: 'New password may not exceed 255 characters',
        groups: ['user:password']
    )]
    public string $newPassword;

    public function __construct(
        string $oldPassword,
        string $newPassword,
    )
    {
        $this->oldPassword = $oldPassword;
        $this->newPassword = $newPassword;
    }
}

?>
