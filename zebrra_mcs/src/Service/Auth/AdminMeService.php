<?php

namespace App\Service\Auth;

use App\Platform\Repository\AdminUserRepository;
use App\DTO\Admin\AdminReadDTO;
use App\Service\Access\AccessControlService;
use App\Service\MailUser\MailUserGatewayService;

final class AdminMeService
{
    public function __construct(
        private readonly AccessControlService $accessControl,
        private readonly AdminUserRepository $adminUserRepository,
        private readonly MailUserGatewayService $mailUserGateway,
    ) {}

    public function me(): array
    {
        $this->accessControl->denyUnlessLogged();
        $this->accessControl->denyUnlessAdmin();
        $admin = $this->accessControl->getActor();

        $adminEntity = $this->adminUserRepository->findOneByEmail($admin->getUserIdentifier());
        $email = $adminEntity->getEmail();

        $mailUserRow = $this->mailUserGateway->findByEmail($email);
        if ($mailUserRow !== null) {
            $hasMailUser = true;
        } else {
            $hasMailUser = false;
        }

        return [
            "data" => AdminReadDTO::fromEntity($admin, $hasMailUser)
        ];
    }
}

?>