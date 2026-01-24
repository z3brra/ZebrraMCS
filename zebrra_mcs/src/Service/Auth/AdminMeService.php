<?php

namespace App\Service\Auth;

use App\DTO\Admin\AdminReadDTO;
use App\Service\Access\AccessControlService;

final class AdminMeService
{
    public function __construct(
        private AccessControlService $accessControl
    ) {}

    public function me(): AdminReadDTO
    {
        $this->accessControl->denyUnlessLogged();
        $admin = $this->accessControl->getAdmin();

        return AdminReadDTO::fromEntity($admin);
    }
}

?>