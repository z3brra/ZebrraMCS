<?php

namespace App\Service\MailUser;

use App\Platform\Repository\MailUserLinkRepository;
use App\DTO\MailUser\{
    MailUserListItemDTO,
    MailUserListResponseDTO,
    MailUserSearchQueryDTO
};
use App\DTO\Common\PaginationMetaDTO;
use App\Platform\Entity\MailUserLink;
use App\Service\Domain\MailDomainLinkResolver;
use App\Service\ValidationService;

use Doctrine\ORM\EntityManagerInterface;

final class SearchMailUserAdminService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidationService $validationService,
        private readonly MailUserLinkRepository $userLinkRepository,
        private readonly MailUserGatewayService $mailUserGateway,
        private readonly MailDomainLinkResolver $domainResolver,
    ) {}

    public function search(MailUserSearchQueryDTO $searchQueryDTO): MailUserListResponseDTO
    {
        $this->validationService->validate($searchQueryDTO, ['user:search']);

        $mailDomainId = null;
        if ($searchQueryDTO->domainUuid !== null) {
            $mailDomainId = $this->domainResolver->resolveMailDomainId($searchQueryDTO->domainUuid);
        }

        $result = $this->mailUserGateway->paginatedByQuery($searchQueryDTO, $mailDomainId);

        $rows = $result['rows'];

        $mailIds = [];
        foreach ($rows as $row) {
            $mailIds[] = (int) $row['id'];
        }

        if ($mailIds === []) {
            $meta = new PaginationMetaDTO(
                page: $result['page'],
                perPage: $result['perPage'],
                total: $result['total'],
                sort: $result['sort'],
                order: $result['order'],
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
            $domainUuid = $this->domainResolver->resolveMailDomainUuid($row['domain_id']);
            if (!isset($map[$mailId])) {
                continue;
            }

            $items[] = new MailUserListItemDTO(
                uuid: (string) $map[$mailId],
                email: (string) $row['email'],
                domainUuid: (string) $domainUuid,
                active: ((int) $row['active']) === 1
            );
        }

        $meta = new PaginationMetaDTO(
            page: $result['page'],
            perPage: $result['perPage'],
            total: $result['total'],
            sort: $result['sort'],
            order: $result['order'],
        );

        return new MailUserListResponseDTO($items, $meta);

    }
}


?>