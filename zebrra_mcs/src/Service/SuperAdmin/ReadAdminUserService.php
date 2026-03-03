<?php

namespace App\Service\SuperAdmin;

use App\Platform\Repository\AdminUserRepository;
use App\DTO\Admin\AdminReadDTO;

use App\Http\Error\ApiException;

final class ReadAdminUserService
{
    public function __construct(
        private readonly AdminUserRepository $adminUserRepository,
    ) {}

    public function read(string $uuid): AdminReadDTO
    {
        $admin = $this->adminUserRepository->findOneByUuid($uuid);
        if (!$admin) {
            throw ApiException::notFound('Admin user not found or does not exist.');
        }

        return AdminReadDTO::fromEntity($admin);
    }
}

?>