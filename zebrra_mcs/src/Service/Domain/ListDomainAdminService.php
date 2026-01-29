<?php

namespace App\Service\Domain;

use App\Platform\Entity\MailDomainLink;
use App\Platform\Repository\MailDomainLinkRepository;

use App\DTO\Domain\{
    DomainListItemDTO,
    DomainListResponseDTO,
};

use App\DTO\Common\PaginationMetaDTO;

use App\Http\Error\ApiException;

use Doctrine\ORM\EntityManagerInterface;

final class ListDomainAdminService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailDomainLinkRepository $domainLinkRepository,
        private readonly MailDomainGatewayService $mailDomainGateway,
    ) {}

    public function list(int $page, int $limit): DomainListResponseDTO
    {
        $result = $this->mailDomainGateway->listPaged($page, $limit);
        $total = (int) $result['total'];
        $rows = $result['rows'];

        $mailIds = [];
        foreach ($rows as $row) {
            $mailIds[] = (int) $row['id'];
        }

        $uuidMap = $this->domainLinkRepository->mapUuidsByMailDomainIds($mailIds);

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
            page: $page,
            perPage: $limit,
            total: $total
        );

        return new DomainListResponseDTO($items, $meta);
    }

}

?>