<?php

namespace App\Service\MailAlias;

use App\Platform\Repository\MailAliasLinkRepository;
use App\DTO\Common\PaginationMetaDTO;
use App\DTO\MailAlias\{
    MailAliasDestinationItemDTO,
    MailAliasListItemDTO,
    MailAliasListResponseDTO,
    MailAliasSearchQueryDTO
};
use App\Service\ValidationService;
use DateTimeImmutable;

final class SearchMailAliasAdminService
{
    public function __construct(
        private readonly MailAliasLinkRepository $mailAliasLinkRepository,
        private readonly ValidationService $validationService,
    ) {}

    public function search(MailAliasSearchQueryDTO $aliasSearchDTO): MailAliasListResponseDTO
    {
        $this->validationService->validate($aliasSearchDTO, ['alias:search']);

        $result = $this->mailAliasLinkRepository->paginateSources(
            q: $aliasSearchDTO->q,
            sort: $aliasSearchDTO->sort ?? 'source',
            order: $aliasSearchDTO->order,
            page: $aliasSearchDTO->page,
            limit: $aliasSearchDTO->limit,
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
                createdAt: new DateTimeImmutable((string) $destination['createdAt']),
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