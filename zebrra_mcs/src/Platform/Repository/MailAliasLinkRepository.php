<?php

namespace App\Platform\Repository;

use App\Platform\Entity\MailAliasLink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;

final class MailAliasLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailAliasLink::class);
    }

    public function findOneByUuid(string $uuid): ?MailAliasLink
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }

    public function findOneByMailAliasId(int $mailAliasId): ?MailAliasLink
    {
        return $this->findOneBy(['mailAliasId' => $mailAliasId]);
    }

    /**
     * @param list<int> $mailAliasIds
     * @return array<int, string> map[mailAliasId] = uuid
     */
    public function mapUuidsByMailAliasIds(array $mailAliasIds): array
    {
        if ($mailAliasIds === []) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('link')
            ->select('link.mailAliasId, link.uuid')
            ->andWhere('link.mailAliasId IN (:ids)')
            ->setParameter('ids', $mailAliasIds);

        $rows = $queryBuilder->getQuery()->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['mailAliasId']] = (string) $row['uuid'];
        }

        return $map;
    }

    /**
     * @return array {
     *      rows: list<array{source_email: string, destinations_count: int, created_at: string}>,
     *      total: int,
     *      page: int,
     *      perPage: int,
     *      sort: int,
     *      order: string,
     * }
     */
    public function paginateSources(?string $q, string $sort, string $order, int $page, int $limit): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);

        $q = $q !== null ? trim($q) : null;
        $needle = ($q !== null && $q !== '') ? '%' . mb_strtolower($q) . '%' : null;

        $sortMap = [
            'source' => 'sourceEmail',
            'destinationsCount' => 'destinationsCount',
            'createdAt' => 'createdAt',
        ];

        $sortField = $sortMap[$sort] ?? 'sourceEmail';
        $orderDir = strtolower($order) === 'desc' ? 'DESC' : 'ASC';

        $connection = $this->getEntityManager()->getConnection();

        $countSql = 'SELECT COUNT(DISTINCT sourceEmail) FROM mail_alias_links';
        $countParams = [];
        $countTypes = [];

        if ($needle !== null) {
            $countSql .= ' WHERE (LOWER(sourceEmail) LIKE :needle OR LOWER(destinationEmail) LIKE :needle)';
            $countParams['needle'] = $needle;
        }

        $total = (int) $connection->fetchOne($countSql, $countParams, $countTypes);

        $offset = ($page - 1) * $limit;

        $dataSql = '
            SELECT
                sourceEmail,
                COUNT(*) AS destinationsCount,
                MIN(createdAt) AS createdAt
            FROM mail_alias_links
        ';

        $dataParams = [];
        if ($needle !== null) {
            $dataSql .= ' WHERE (LOWER(sourceEmail) LIKE :needle OR LOWER(destinationEmail) LIKE :needle)';
            $dataParams['needle'] = $needle;
        }

        $dataSql .= '
            GROUP BY sourceEmail
            ORDER BY '.$sortField.' '.$orderDir.', sourceEmail ASC
            LIMIT :limit OFFSET :offset
        ';

        $dataParams['limit'] = $limit;
        $dataParams['offset'] = $offset;

        $rows = $connection->fetchAllAssociative(
            $dataSql,
            $dataParams,
            [
                'limit' => ParameterType::INTEGER,
                'offset' => ParameterType::INTEGER
            ]
        );

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'perPage' => $limit,
            'sort' => $sortField,
            'order' => $orderDir,
        ];
    }

    /**
     * @param list<string> $sources
     * @return list<array{uuid:string,source_email:string,destination_email:string,created_at:string}>
     */
    public function findDestinationsForSources(array $sources): array
    {
        if ($sources === []) {
            return [];
        }

        $connection = $this->getEntityManager()->getConnection();

        $placeholders = [];
        $params = [];
        $i = 0;

        foreach ($sources as $source) {
            $key = 'source'.$i;
            $placeholders[] = ':'.$key;
            $params[$key] = $source;
            $i++;
        }

        $sql = '
            SELECT uuid, sourceEmail, destinationEmail, createdAt
            FROM mail_alias_links
            WHERE sourceEmail IN ('.implode(',', $placeholders).')
            ORDER BY sourceEmail ASC, createdAt ASC
        ';

        return $connection->fetchAllAssociative($sql, $params);
    }
}

?>