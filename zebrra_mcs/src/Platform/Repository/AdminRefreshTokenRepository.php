<?php

namespace App\Platform\Repository;

use App\Platform\Entity\AdminRefreshToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class AdminRefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminRefreshToken::class);
    }

    public function findActiveByHash(string $tokenHash): ?AdminRefreshToken
    {
        return $this->createQueryBuilder('token')
            ->andWhere('token.tokenHash = :hash')->setParameter('hash', $tokenHash)
            ->andWhere('token.revokedAt IS NULL')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveByAdminId(int $adminId): array
    {
        return $this->createQueryBuilder('token')
            ->andWhere('IDENTITY(token.adminUser) = :id')->setParameter('id', $adminId)
            ->andWhere('token.revokedAt IS NULL')
            ->getQuery()
            ->getResult();
    }
}

?>