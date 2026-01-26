<?php

namespace App\Service\Auth;

use App\DTO\Admin\AdminReadDTO;
use App\Service\Access\AccessControlService;

final class AdminMeService
{
    public function __construct(
        private AccessControlService $accessControl
    ) {}

    public function me(): array
    {
        $this->accessControl->denyUnlessLogged();
        $this->accessControl->denyUnlessAdmin();
        $admin = $this->accessControl->getActor();

        return [
            "data" => AdminReadDTO::fromEntity($admin)
        ];
    }
}

?>