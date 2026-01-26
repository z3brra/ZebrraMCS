<?php

namespace App\Platform\Repository;

use App\Platform\Entity\ApiToken;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
}

?>