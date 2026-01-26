<?php

namespace App\Platform\Repository;

use App\DTO\Token\TokenListQueryDTO;
use App\Platform\Entity\ApiToken;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

final class ApiTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiToken::class);
    }

    public function findOneByTokenHash(string $tokenHash): ?ApiToken
    {
        return $this->findOneBy(['tokenHash' => $tokenHash]);
    }

    public function findOneByUuid(string $uuid): ?ApiToken
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }

    public function paginateByQuery(TokenListQueryDTO $query): array
    {
        $queryBuilder = $this->createQueryBuilder('token')
            ->leftJoin('token.createdByAdmin', 'admin')
            ->addSelect('admin');

        // Search
        if ($query->q !== null && trim($query->q) !== '') {
            $needle = '%'.mb_strtolower(trim($query->q)).'%';
            $queryBuilder->andWhere('LOWER(token.name) LIKE :needle OR LOWER(token.uuid) LIKE :needle')
                ->setParameter('needle', $needle);
        }

        // Filters
        if ($query->active !== null) {
            $queryBuilder->andWhere('token.active = :active')
                ->setParameter('active', $query->active);
        }

        if ($query->revoked !== null) {
            $queryBuilder->andWhere($query->revoked ? 'token.revokedAt IS NOT NULL' : 'token.revokedAt IS NULL');
        }

        if ($query->expired !== null) {
            if ($query->expired) {
                $queryBuilder->andWhere('token.expiresAt IS NOT NULL AND token.expiresAt <= :now')
                    ->setParameter('now', new DateTimeImmutable());
            } else {
                $queryBuilder->andWhere('(token.expiresAt IS NULL OR token.expiresAt > :now)')
                    ->setParameter('now', new DateTimeImmutable());
            }
        }

        if ($query->createdByAdminUuid !== null) {
            $queryBuilder->andWhere('admin.uuid = :adminUuid')
                ->setParameter('adminUuid', $query->createdByAdminUuid);
        }

        // Sorting
        $sortMap = [
            'createdAt' => 'token.createdAt',
            'lastUsedAt' => 'token.lastUsedAt',
            'expiresAt' => 'token.expiresAt',
            'name' => 'token.name',
        ];

        $sortField = $sortMap[$query->sort] ?? 'token.createdAt';
        $order = strtolower($query->order) === 'asc' ? 'ASC' : 'DESC';

        $queryBuilder->orderBy($sortField, $order);

        // Pagination
        $page = max(1, $query->page);
        $limit = max(1, $query->limit);

        $queryBuilder->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $paginator = new Paginator($queryBuilder, true);

        $data = iterator_to_array($paginator->getIterator(), false);
        $total = count($paginator);

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'perPage' => $limit,
            'sort' => $sortField,
            'order' => $order,
        ];
    }
}

?>