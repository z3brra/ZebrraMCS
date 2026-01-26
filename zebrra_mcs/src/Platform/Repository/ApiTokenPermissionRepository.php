<?php

namespace App\Platform\Repository;

use App\Platform\Entity\ApiToken;
use App\Platform\Entity\ApiTokenPermission;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class ApiTokenPermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiTokenPermission::class);
    }

    public function findByToken(ApiToken $token): ?array
    {
        return $this->findBy(['token' => $token]);
    }
}

?>