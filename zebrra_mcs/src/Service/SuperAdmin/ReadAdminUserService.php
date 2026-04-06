<?php

namespace App\Service\SuperAdmin;

use App\Platform\Repository\AdminUserRepository;
use App\DTO\Admin\AdminReadDTO;

use App\Service\MailUser\MailUserGatewayService;

use App\Http\Error\ApiException;

final class ReadAdminUserService
{
    public function __construct(
        private readonly AdminUserRepository $adminUserRepository,
        private readonly MailUserGatewayService $mailUserGateway,
    ) {}

    public function read(string $uuid): AdminReadDTO
    {
        $admin = $this->adminUserRepository->findOneByUuid($uuid);
        if (!$admin) {
            throw ApiException::notFound('Admin user not found or does not exist.');
        }

        $mailUserRow = $this->mailUserGateway->findByEmail($admin->getEmail());
        if ($mailUserRow !== null) {
            $hasMailUser = true;
        } else {
            $hasMailUser = false;
        }


        return AdminReadDTO::fromEntity($admin, $hasMailUser);
    }
}

?>