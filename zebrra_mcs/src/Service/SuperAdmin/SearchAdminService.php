<?php

namespace App\Service\SuperAdmin;

use App\Platform\Entity\AdminUser;
use App\Platform\Repository\AdminUserRepository;
use App\DTO\Admin\{
    AdminListItemDTO,
    AdminListResponseDTO,
    AdminSearchQueryDTO,
};
use App\DTO\Common\PaginationMetaDTO;
use App\Service\MailUser\MailUserGatewayService;
use App\Service\ValidationService;

final class SearchAdminService
{
    public function __construct(
        private readonly ValidationService $validationService,
        private readonly AdminUserRepository $adminUserRepository,
        private readonly MailUserGatewayService $mailUserGateway,
    ) {}

    public function search(AdminSearchQueryDTO $searchAdminDTO): AdminListResponseDTO
    {
        $this->validationService->validate($searchAdminDTO, ['admin:search']);

        $result = $this->adminUserRepository->paginateByQuery($searchAdminDTO);

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

            if ($searchAdminDTO->hasMailbox !== null && $hasMailBox !== $searchAdminDTO->hasMailbox) {
                continue;
            }

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
            total: (int) $result['total'],
            sort: (string) $result['sort'],
            order: (string) $result['order']
        );

        return new AdminListResponseDTO($items, $meta);
    }
}


?>