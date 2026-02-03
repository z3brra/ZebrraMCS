<?php

namespace App\Platform\Repository;

use App\Platform\Entity\MailDomainLink;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class MailDomainLinkRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry
    ) {
        parent::__construct($registry, MailDomainLink::class);
    }

    public function findOneByUuid(string $uuid): ?MailDomainLink
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }

    public function findOneByMailDomainId(int $mailDomainId): ?MailDomainLink
    {
        return $this->findOneBy(['mailDomainId' => $mailDomainId]);
    }

    public function existsByUuid(string $uuid): bool
    {
        return (bool) $this->createQueryBuilder('link')
            ->select('1')
            ->andWhere('link.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param list<int> $mailDomainIds
     * @return array<int, string> map mail_domain_id => uuid
     */
    public function mapUuidsByMailDomainIds(array $mailDomainIds): array
    {
        if ($mailDomainIds === []) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('link')
            ->select('link.mailDomainId, link.uuid')
            ->andWhere('link.mailDomainId IN (:ids)')
            ->setParameter('ids', $mailDomainIds);

        $rows = $queryBuilder->getQuery()->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['mailDomainId']] = (string) $row['uuid'];
        }

        return $map;
    }
}

?>