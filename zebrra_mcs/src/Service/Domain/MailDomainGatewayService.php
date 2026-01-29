<?php

namespace App\Service\Domain;

use App\DTO\Domain\DomainSearchQueryDTO;
use Doctrine\DBAL\Connection;

final class MailDomainGatewayService
{
    public function __construct(
        private readonly Connection $mailConnection
    ) {}

    /**
     * @return array{id: int, name: string, active: int}|null
     */
    public function findById(int $id): ?array
    {
        $row = $this->mailConnection->fetchAssociative(
            'SELECT id, name, active FROM domains WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
        return $row ?: null;
    }

    /**
     * @return array{id: int, name: string, active: int}|null
     */
    public function findByName(string $name): ?array
    {
        $row = $this->mailConnection->fetchAssociative(
            'SELECT id, name, active FROM domains WHERE name = :name LIMIT 1',
            ['name' => $name]
        );

        return $row ?: null;
    }

    /**
     * @return int newly created mailserver.domains.id
     */
    public function insert(string $name, bool $active): int
    {
        $this->mailConnection->insert('domains', [
            'name' => $name,
            'active' => $active ? 1 : 0,
        ]);

        return (int) $this->mailConnection->lastInsertId();
    }

    /**
     * @return array{total: int, rows: list<array{id: int, name: string, active: int}>}
     */
    public function listPaged(int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        $total = (int) $this->mailConnection->fetchOne('SELECT COUNT(*) FROM domains');

        $queryBuilder = $this->mailConnection->createQueryBuilder();
        $queryBuilder->select('domain.id', 'domain.name', 'domain.active')
            ->from('domains', 'domain')
            ->addOrderBy('domain.active', 'DESC')
            ->addOrderBy('domain.name', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $rows = $queryBuilder->executeQuery()->fetchAllAssociative();

        return [
            'total' => $total,
            'rows' => $rows,
        ];
    }

    public function paginatedByQuery(DomainSearchQueryDTO $query): array
    {
        // Pagination
        $page = max(1, $query->page);
        $limit = max(1, $query->limit);

        // Filters
        $needle = null;
        if ($query->q !== null && trim($query->q) !== '') {
            $needle = '%' . trim($query->q) . '%';
        }

        // Sorting
        $sortMap = [
            'name' => 'domain.name',
            'active' => 'domain.active'
        ];

        $sortField = $sortMap[$query->sort] ?? 'domain.active';
        $order = strtolower($query->order) === 'asc' ? 'ASC' : 'DESC';

        $applyFilters = function ($queryBuilder) use ($needle, $query): void {
            if ($needle !== null) {
                $queryBuilder->andWhere('domain.name LIKE :needle')
                    ->setParameter('needle', $needle);
            }

            if ($query->active !== null) {
                $queryBuilder->andWhere('domain.active = :active')
                    ->setParameter('active', $query->active ? 1 : 0);
            }
        };

        $countQueryBuilder = $this->mailConnection->createQueryBuilder();
        $countQueryBuilder->select('COUNT(*)')
            ->from('domains', 'domain');
        $applyFilters($countQueryBuilder);

        $total = (int) $countQueryBuilder->executeQuery()->fetchOne();

        // data
        $dataQueryBuilder = $this->mailConnection->createQueryBuilder();
        $dataQueryBuilder->select('domain.id', 'domain.name', 'domain.active')
            ->from('domains', 'domain');
        $applyFilters($dataQueryBuilder);

        $dataQueryBuilder->addOrderBy($sortField, $order);

        if ($sortField !== 'domain.name') {
            $dataQueryBuilder->addOrderBy('domain.name', 'ASC');
        } else {
            $dataQueryBuilder->addOrderBy('domain.id', 'ASC');
        }

        $dataQueryBuilder->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $rows = $dataQueryBuilder->executeQuery()->fetchAllAssociative();

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'perPage' => $limit,
            'sort' => $sortField,
            'order' => $order
        ];
    }
}

?>