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

    // public function search(AdminSearchQueryDTO $searchAdminDTO): AdminListResponseDTO
    // {
    //     $this->validationService->validate($searchAdminDTO, ['admin:search']);

    //     $result = $this->adminUserRepository->paginateByQuery($searchAdminDTO);

    //     /** @var list<AdminUser> $admins */
    //     $admins = $result['data'];

    //     $emails = [];
    //     foreach ($admins as $admin) {
    //         $emails[] = mb_strtolower((string) $admin->getEmail());
    //     }
    //     $mailMap = $this->mailUserGateway->mapExistingByEmails($emails);

    //     $items = [];
    //     foreach ($admins as $admin) {
    //         $email = mb_strtolower((string) $admin->getEmail());
    //         $hasMailBox = $mailMap[$email] ?? false;

    //         if ($searchAdminDTO->hasMailbox !== null && $hasMailBox !== $searchAdminDTO->hasMailbox) {
    //             continue;
    //         }

    //         $items[] = new AdminListItemDTO(
    //             uuid: $admin->getUuid(),
    //             email: $email,
    //             roles: $admin->getRoles(),
    //             active: $admin->isActive(),
    //             isDeleted: $admin->isDeleted(),
    //             hasMailbox: $hasMailBox,
    //             createdAt: $admin->getCreatedAt()
    //         );
    //     }

    //     $meta = new PaginationMetaDTO(
    //         page: (int) $result['page'],
    //         perPage: (int) $result['perPage'],
    //         total: (int) $result['total'],
    //         sort: (string) $result['sort'],
    //         order: (string) $result['order']
    //     );

    //     return new AdminListResponseDTO($items, $meta);
    // }

    public function search(AdminSearchQueryDTO $searchAdminDTO): AdminListResponseDTO
    {
        $this->validationService->validate($searchAdminDTO, ['admin:search']);

        $page = max(1, (int) $searchAdminDTO->page);
        $limit = max(1, (int) $searchAdminDTO->limit);

        $pack = $this->adminUserRepository->qbByQuery($searchAdminDTO);

        /**
         * @var \Doctrine\ORM\QueryBuilder $queryBuilder
         */
        $queryBuilder = $pack['queryBuilder'];
        $sortField = $pack['sortField'];
        $orderDir = $pack['orderDir'];

        if ($searchAdminDTO->hasMailbox !== null) {
            $candidateEmails = $this->adminUserRepository->findCandidateEmails($searchAdminDTO);

            if (count($candidateEmails) === 0) {
                $meta = new PaginationMetaDTO(
                    page: 1,
                    perPage: $limit,
                    total: 0,
                    sort: $sortField,
                    order: $orderDir,
                );

                return new AdminListResponseDTO([], $meta);
            }

            $mailMap = $this->mailUserGateway->mapExistingByEmails($candidateEmails);

            $emailsWithMailbox = [];
            foreach ($candidateEmails as $email) {
                if (($mailMap[$email] ?? false) === true) {
                    $emailsWithMailbox[] = $email;
                }
            }

            if ($searchAdminDTO->hasMailbox === true) {
                if (count($emailsWithMailbox) === 0) {
                    $meta = new PaginationMetaDTO(
                        page: 1,
                        perPage: $limit,
                        total: 0,
                        sort: $sortField,
                        order: $orderDir
                    );

                    return new AdminListResponseDTO([], $meta);
                }

                $queryBuilder->andWhere('LOWER(admin.email) IN (:mailEmails)')
                    ->setParameter('mailEmails', $emailsWithMailbox);
            } else {
                if (count($emailsWithMailbox) > 0) {
                    $queryBuilder->andWhere('LOWER(admin.email) NOT IN (:mailEmails)')
                        ->setParameter('mailEmails', $emailsWithMailbox);
                }
            }
        }

        $result = $this->adminUserRepository->paginateQb($queryBuilder, $page, $limit);

        /** @var list<AdminUser> $admins */
        $admins = $result['data'];
        $total = (int) $result['total'];
        $totalPages = (int) max(1, (int) ceil($total / $limit));

        $page = min($page, $totalPages);

        $emailsPage = [];
        foreach ($admins as $admin) {
            $emailsPage[] = mb_strtolower((string) $admin->getEmail());
        }
        $mailMapPage = $this->mailUserGateway->mapExistingByEmails($emailsPage);

        $items = [];
        foreach ($admins as $admin) {
            $email = mb_strtolower((string) $admin->getEmail());
            $hasMailbox = $mailMapPage[$email] ?? false;

            $items[] = new AdminListItemDTO(
                uuid: $admin->getUuid(),
                email: $email,
                roles: $admin->getRoles(),
                active: $admin->isActive(),
                isDeleted: $admin->isDeleted(),
                hasMailbox: $hasMailbox,
                createdAt: $admin->getCreatedAt(),
            );
        }

        $meta = new PaginationMetaDTO(
            page: (int) $result['page'],
            perPage: (int) $result['perPage'],
            total: $total,
            sort: $sortField,
            order: $orderDir,
        );

        return new AdminListResponseDTO($items, $meta);
    }
}


?>