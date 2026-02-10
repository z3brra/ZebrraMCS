<?php

namespace App\Platform\Repository;

use App\Platform\Entity\MailUserLink;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class MailUserLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) 
    {
        parent::__construct($registry, MailUserLink::class);
    }

    public function findOneByUuid(string $uuid): ?MailUserLink
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }

    public function findOneByMailUserId(int $mailUserId): ?MailUserLink
    {
        return $this->findOneBy(['mailUserId' => $mailUserId]);
    }

    /**
     * @param list<int> $mailUserIds
     * @return array<int, string> map[mail_user_id => uuid]
     */
    public function mapUuidsByMailUserIds(array $mailUserIds): array
    {
        if ($mailUserIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('link')
            ->select('link.mailUserId, link.uuid')
            ->andWhere('link.mailUserId IN (:ids)')
            ->setParameter('ids', $mailUserIds)
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['mailUserId']] = (string) $row['uuid'];
        }

        return $map;
    }
}

?>