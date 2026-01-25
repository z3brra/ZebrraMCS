<?php

namespace App\Platform\Repository;

use App\Platform\Entity\ApiTokenScope;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class ApiTokenScopeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiTokenScope::class);
    }
}

?>
