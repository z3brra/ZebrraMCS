<?php

namespace App\Service\MailUser;

use App\Platform\Repository\MailUserLinkRepository;
use App\DTO\MailUser\{
    MailUserListItemDTO,
    MailUserListResponseDTO
};
use App\DTO\Common\PaginationMetaDTO;
use App\Platform\Entity\MailUserLink;
use App\Service\Domain\MailDomainLinkResolver;
use Doctrine\ORM\EntityManagerInterface;

final class ListMailUserAdminService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailUserLinkRepository $userLinkRepository,
        private readonly MailUserGatewayService $mailUserGateway,
        private readonly MailDomainLinkResolver $domainResolver
    ) {}

    public function list(int $page, int $limit): MailUserListResponseDTO
    {
        $result = $this->mailUserGateway->listPaged($page, $limit);

        $rows = $result['rows'];
        $total = (int) $result['total'];

        $mailIds = [];
        foreach ($rows as $row) {
            $mailIds[] = (int) $row['id'];
        }

        if ($mailIds === []) {
            $meta = new PaginationMetaDTO(
                page: $page,
                perPage: $limit,
                total: $total,
            );
            return new MailUserListResponseDTO([], $meta);
        }

        $map = $this->userLinkRepository->mapUuidsByMailUserIds($mailIds);

        $missingRows = [];
        foreach ($rows as $row) {
            $mailId = (int) $row['id'];
            if (!isset($map[$mailId])) {
                $missingRows[] = $row;
            }
        }

        if ($missingRows !== []) {
            foreach ($missingRows as $row) {
                $mailId = (int) $row['id'];
                $mailDomainId = (int) $row['domain_id'];

                $link = new MailUserLink(
                    mailUserId: $mailId,
                    mailDomainId: $mailDomainId,
                    email: $row['email']
                );
                $this->entityManager->persist($link);
            }
            $this->entityManager->flush();
            $map = $this->userLinkRepository->mapUuidsByMailUserIds($mailIds);
        }

        $items = [];
        foreach ($rows as $row) {
            $mailId = (int) $row['id'];
            if (!isset($map[$mailId])) {
                continue;
            }

            $domainUuid = $this->domainResolver->resolveMailDomainUuid($row['domain_id']);

            $items[] = new MailUserListItemDTO(
                uuid: (string) $map[$mailId],
                email: (string) $row['email'],
                domainUuid: (string) $domainUuid,
                active: ((int) $row['active']) === 1
            );
        }

        $meta = new PaginationMetaDTO(
            page: $page,
            perPage: $limit,
            total: $total,
        );

        return new MailUserListResponseDTO($items, $meta);
    }
}

?>