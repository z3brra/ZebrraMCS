<?php

namespace App\Service\Domain;

use App\Platform\Repository\MailDomainLinkRepository;
use App\DTO\Domain\{
    DomainListResponseDTO,
    DomainSearchQueryDTO,
    DomainListItemDTO,
};
use App\DTO\Common\PaginationMetaDTO;

use App\Platform\Entity\MailDomainLink;
use App\Service\ValidationService;

use Doctrine\ORM\EntityManagerInterface;

final class SearchDomainAdminService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailDomainLinkRepository $domainLinkRepository,
        private readonly MailDomainGatewayService $mailDomainGateway,
        private readonly ValidationService $validationService,
    ) {}

    public function search(DomainSearchQueryDTO $queryDTO): DomainListResponseDTO
    {
        $this->validationService->validate($queryDTO, ['domain:search']);

        $result = $this->mailDomainGateway->paginatedByQuery($queryDTO);

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
                order: $result['order']
            );
            return new DomainListResponseDTO([], $meta);
        }

        $uuidMap = $this->domainLinkRepository->mapUuidsByMailDomainIds($mailIds);

        $missingIds = [];
        foreach ($mailIds as $mailId) {
            if (!isset($uuidMap[$mailId])) {
                $missingIds[] = $mailId;
            }
        }

        if ($missingIds !== []) {
            foreach ($missingIds as $mailId) {
                $link = new MailDomainLink($mailId);
                $this->entityManager->persist($link);
            }
            $this->entityManager->flush();

            $uuidMap = $this->domainLinkRepository->mapUuidsByMailDomainIds($mailIds);
        }

        $items = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $uuid = $uuidMap[$id] ?? null;

            if ($uuid === null) {
                continue;
            }

            $items[] = new DomainListItemDTO(
                uuid: $uuid,
                name: (string) $row['name'],
                active: ((int) $row['active']) === 1
            );
        }

        $meta = new PaginationMetaDTO(
            page: $result['page'],
            perPage: $result['perPage'],
            total: $result['total'],
            sort: $result['sort'],
            order: $result['order']
        );

        return new DomainListResponseDTO($items, $meta);
    }
}

?>