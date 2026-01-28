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
}

?>