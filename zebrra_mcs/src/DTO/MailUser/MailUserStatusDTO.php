<?php

namespace App\DTO\MailUser;

use App\Enum\MailUserStatusAction;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class MailUserStatusDTO
{
    #[Assert\NotBlank(message: 'Action is required.', groups: ['user:status'])]
    #[Assert\Choice(choices: ['enable', 'disable'], groups: ['user:status'])]
    public string $action;

    public function __construct(
        string $action,
    ) {
        $this->action = $action;
    }

    public function toEnum(): MailUserStatusAction
    {
        return MailUserStatusAction::from($this->action);
    }
}

?>