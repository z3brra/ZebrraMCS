<?php

namespace App\Service\SuperAdmin;

use App\Platform\Entity\AdminUser;
use App\Platform\Repository\AdminUserRepository;
use App\DTO\Admin\{
    AdminListItemDTO,
    AdminListResponseDTO
};
use App\DTO\Common\PaginationMetaDTO;

use App\Service\MailUser\MailUserGatewayService;

final class ListAdminService
{
    public function __construct(
        private readonly AdminUserRepository $adminUserRepository,
        private readonly MailUserGatewayService $mailUserGateway,
    ) {}

    public function list(int $page, int $limit): AdminListResponseDTO
    {
        $result = $this->adminUserRepository->listPaginated($page, $limit);

        /** @var list<AdminUser> $admins */
        $admins = $result['data'];

        $emails = [];
        foreach ($admins as $admin) {
            $emails[] = mb_strtolower((string) $admin->getEmail());
        }
        $mailMap = $this->mailUserGateway->mapExistingByEmails($emails);

        $items = [];
        foreach ($admins as $admin) {
            $email = mb_strtolower((string) $admin->getEmail());
            $hasMailBox = $mailMap[$email] ?? false;

            $items[] = new AdminListItemDTO(
                uuid: $admin->getUuid(),
                email: $email,
                roles: $admin->getRoles(),
                active: $admin->isActive(),
                isDeleted: $admin->isDeleted(),
                hasMailbox: $hasMailBox,
                createdAt: $admin->getCreatedAt()
            );
        }

        $meta = new PaginationMetaDTO(
            page: (int) $result['page'],
            perPage: (int) $result['perPage'],
            total: (int) $result['total']
        );

        return new AdminListResponseDTO($items, $meta);
    }
}


?>