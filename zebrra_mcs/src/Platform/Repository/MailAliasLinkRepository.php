<?php

namespace App\Platform\Repository;

use App\Platform\Entity\MailAliasLink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
}

?>