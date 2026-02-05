<?php

namespace App\DTO\Domain;

use App\Enum\DomainStatusAction;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class DomainStatusPatchDTO
{
    #[Groups(['domain:status'])]
    #[Assert\NotBlank(message: 'Action is required.', groups: ['domain:status'])]
    #[Assert\Choice(choices: ['enable', 'disable'], groups: ['domain:status'])]
    public string $action;

    public function __construct(
        string $action = 'disable',
    ) {
        $this->action = $action;
    }

    public function toEnum(): DomainStatusAction
    {
        return DomainStatusAction::from($this->action);
    }
}

?>