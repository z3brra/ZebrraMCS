<?php

namespace App\Service\MailAlias;

use App\Platform\Repository\MailAliasLinkRepository;
use App\DTO\Common\PaginationMetaDTO;
use App\DTO\MailAlias\{
    MailAliasDestinationItemDTO,
    MailAliasListItemDTO,
    MailAliasListResponseDTO
};
use DateTimeImmutable;

final class ListMailAliasAdminService
{
    public function __construct(
        private readonly MailAliasLinkRepository $mailAliasLinkRepository
    ) {}

    public function list(int $page, int $limit): MailAliasListResponseDTO
    {
        $result = $this->mailAliasLinkRepository->paginateSources(
            q: null,
            sort: 'source',
            order: 'asc',
            page: $page,
            limit: $limit
        );

        $sources = [];
        foreach ($result['rows'] as $row) {
            $sources[] = (string) $row['sourceEmail'];
        }

        $destRows = $this->mailAliasLinkRepository->findDestinationsForSources($sources);

        $destBySource = [];
        foreach ($destRows as $destination) {
            $source = (string) $destination['sourceEmail'];
            if (!isset($destBySource[$source])) {
                $destBySource[$source] = [];
            }
            $destBySource[$source][] = new MailAliasDestinationItemDTO(
                uuid: (string) $destination['uuid'],
                destinationEmail: (string) $destination['destinationEmail'],
                createdAt: new DateTimeImmutable((string) $destination['createdAt'])
            );
        }

        $items = [];
        foreach ($result['rows'] as $row) {
            $source = (string) $row['sourceEmail'];

            $items[] = new MailAliasListItemDTO(
                sourceEmail: $source,
                destinationCount: (int) $row['destinationsCount'],
                createdAt: new DateTimeImmutable((string) $row['createdAt']),
                destinations: $destBySource[$source] ?? []
            );
        }

        $meta = new PaginationMetaDTO(
            page: $result['page'],
            perPage: $result['perPage'],
            total: $result['total'],
            sort: $result['sort'],
            order: $result['order'],
        );

        return new MailAliasListResponseDTO($items, $meta);
    }
}

?>