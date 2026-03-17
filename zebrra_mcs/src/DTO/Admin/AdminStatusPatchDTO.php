<?php

namespace App\DTO\Admin;

use App\Enum\AdminStatusAction;
use Symfony\Component\Validator\Constraints as Assert;

final class AdminStatusPatchDTO
{
    #[Assert\NotBlank(message: 'Action is required.', groups: ['admin:status'])]
    #[Assert\Choice(choices: ['enable', 'disable'], groups: ['admin:status'])]
    public string $action;

    public function __construct(
        string $action,
    ) {
        $this->action = $action;
    }

    public function toEnum(): AdminStatusAction
    {
        return AdminStatusAction::from($this->action);
    }
}

?>