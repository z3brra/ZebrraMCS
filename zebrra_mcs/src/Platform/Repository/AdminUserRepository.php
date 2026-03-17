<?php

namespace App\Platform\Repository;

use App\DTO\Admin\AdminSearchQueryDTO;
use App\Platform\Entity\AdminUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<AdminUser>
 */
class AdminUserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminUser::class);
    }

    public function findOneByUuid(string $uuid): ?AdminUser
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }

    public function findOneByEmail(string $email): ?AdminUser
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof AdminUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function listPaginated(int $page, int $limit): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);

        $queryBuilder = $this->createQueryBuilder('admin')
            ->orderBy('admin.createdAt', 'DESC')
            ->addOrderBy('admin.email', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);
        
        $paginator = new Paginator($queryBuilder, true);

        $data = iterator_to_array($paginator->getIterator(), false);
        $total = count($paginator);

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'perPage' => $limit,
        ];
    }

    public function paginateByQuery(AdminSearchQueryDTO $query): array
    {
        $queryBuilder = $this->createQueryBuilder('admin');

        if ($query->q !== null && trim($query->q) !== '') {
            $needle = '%'.mb_strtolower(trim($query->q)).'%';
            $queryBuilder->andWhere('LOWER(admin.email) LIKE :needle OR LOWER(admin.uuid) LIKE :needle')
                ->setParameter('needle', $needle);
        }

        if ($query->active !== null) {
            $queryBuilder->andWhere('admin.active = :active')
                ->setParameter('active', $query->active);
        }

        if ($query->deleted !== null) {
            $queryBuilder->andWhere($query->deleted ? 'admin.deletedAt IS NOT NULL' : 'admin.deletedAt IS NULL');
        }

        $sortMap = [
            'email' => 'admin.email',
            'createdAt' => 'admin.createdAt',
            'active' => 'admin.active',
        ];

        $sortField = $sortMap[$query->sort ?? 'createdAt'] ?? 'admin.createdAt';
        $orderDir = strtolower($query->order) === 'asc' ? 'ASC' : 'DESC';

        $queryBuilder->orderBy($sortField, $orderDir);

        if ($sortField !== 'admin.email') {
            $queryBuilder->addOrderBy('admin.email', 'ASC');
        }

        $page = max(1, $query->page);
        $limit = max(1, $query->limit);

        $queryBuilder->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $paginator = new Paginator($queryBuilder, true);
        $result = iterator_to_array($paginator->getIterator(), false);
        $total = count($paginator);

        return [
            'data' => $result,
            'total' => $total,
            'page' => $page,
            'perPage' => $limit,
            'sort' => $sortField,
            'order' => $orderDir,
        ];
    }

    public function qbByQuery(AdminSearchQueryDTO $query): array
    {
        $queryBuilder = $this->createQueryBuilder('admin');

        if ($query->q !== null && trim($query->q) !== '') {
            $needle = '%'.mb_strtolower(trim($query->q)).'%';
            $queryBuilder->andWhere('LOWER(admin.email) LIKE :needle OR LOWER(admin.uuid) LIKE :needle')
                ->setParameter('needle', $needle);
        }

        if ($query->active !== null) {
            $queryBuilder->andWhere('admin.active = :active')
                ->setParameter('active', $query->active);
        }

        if ($query->deleted !== null) {
            $queryBuilder->andWhere($query->deleted ? 'admin.deletedAt IS NOT NULL' : 'admin.deleteAt IS NULL');
        }

        $sortMap = [
            'email' => 'admin.email',
            'createdAt' => 'admin.createdAt',
            'active' => 'admin.active',
        ];

        $sortField = $sortMap[$query->sort ?? 'createdAt'] ?? 'admin.createdAt';
        $orderDir = strtolower((string) $query->order) === 'asc' ? 'ASC' : 'DESC';

        $queryBuilder->orderBy($sortField, $orderDir);

        if ($sortField !== 'admin.email') {
            $queryBuilder->addOrderBy('admin.email', 'ASC');
        }

        return [
            'queryBuilder' => $queryBuilder,
            'sortField' => $sortField,
            'orderDir' => $orderDir
        ];
    }

    public function findCandidateEmails(AdminSearchQueryDTO $query): array
    {
        $pack = $this->qbByQuery($query);
        
        /**
         * @var \Doctrine\ORM\QueryBuilder $queryBuilder
         */
        $queryBuilder = $pack['queryBuilder'];

        $rows = $queryBuilder
            ->select('LOWER(admin.email) AS email')
            ->getQuery()
            ->getArrayResult();

        $emails = [];
        foreach ($rows as $row) {
            if (isset($row['email']) && is_string($row['email']) && $row['email'] !== '') {
                $emails[] = $row['email'];
            }
        }

        return $emails;
    }

    public function paginateQb(QueryBuilder $queryBuilder, int $page, int $limit): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);

        $queryBuilder->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $paginator = new Paginator($queryBuilder, true);

        $data = iterator_to_array($paginator->getIterator(), false);

        return [
            'data' => $data,
            'total' => count($paginator),
            'page' => $page,
            'perPage' => $limit
        ];
    }


//    /**
//     * @return AdminUser[] Returns an array of AdminUser objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?AdminUser
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
