<?php

namespace App\Service\MailUser;

use App\DTO\MailUser\MailUserSearchQueryDTO;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final class MailUserGatewayService
{
    public function __construct(
        private readonly Connection $mailConnection
    ) {}

    public function create(
        int $mailDomainId,
        string $email,
        string $passwordHash,
        bool $active = true
    ): int {
        $this->mailConnection->insert('users', [
            'domain_id' => $mailDomainId,
            'email' => strtolower($email),
            'password' => $passwordHash,
            'active' => $active ? 1 : 0,
        ]);

        return (int) $this->mailConnection->lastInsertId();
    }

    /**
     * @return array{id: int, domain_id: int, email: string, password: string, active: int}|null
     */
    public function findByEmail(string $email): ?array
    {
        $row = $this->mailConnection->fetchAssociative(
            'SELECT id, domain_id, email, password, active FROM users WHERE email = :email LIMIT 1',
            ['email' => $email]
        );

        return $row === false ? null : [
            'id' => (int) $row['id'],
            'domain_id' => (int) $row['domain_id'],
            'email' => (string) $row['email'],
            'password' => (string) $row['password'],
            'active' => (int) $row['active'],
        ];
    }

    /**
     * @return array{id: int, domain_id: int, email: string, password: string, active: int}|null
     */
    public function findById(int $mailUserId): ?array
    {
        $row = $this->mailConnection->fetchAssociative(
            'SELECT id, domain_id, email, password, active FROM users WHERE id = :id LIMIT 1',
            ['id' => $mailUserId]
        );

        return $row === false ? null : [
            'id' => (int) $row['id'],
            'domain_id' => (int) $row['domain_id'],
            'email' => (string) $row['email'],
            'password' => (string) $row['password'],
            'active' => (int) $row['active'],
        ];
    }

    public function existsByEmail(string $email): bool
    {
        $exists = $this->mailConnection->fetchOne(
            'SELECT 1 FROM users WHERE email = :email LIMIT 1',
            ['email' => $email]
        );

        return (string) $exists === '1';
    }

    public function listAll(?int $limit = null): array
    {
        $sql = 'SELECT id, email, domain_id FROM users ORDER BY id ASC';

        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        return $this->mailConnection->fetchAllAssociative($sql);
    }

    public function setActive(int $mailUserId, bool $active): void
    {
        $this->mailConnection->executeStatement(
            'UPDATE users SET active = :active WHERE id = :id',
            [
                'active' => $active ? 1 : 0,
                'id' => $mailUserId
            ],
            [
                'active' => ParameterType::INTEGER,
                'id' => ParameterType::INTEGER
            ]
        );
    }

    public function getPasswordHashById(int $mailUserId): ?string
    {
        $hash = $this->mailConnection->fetchOne(
            'SELECT password FROM users WHERE id = :id LIMIT 1',
            ['id' => $mailUserId],
            ['id' => ParameterType::INTEGER]
        );

        if ($hash === false || $hash === null) {
            return null;
        }

        return (string) $hash;
    }

    public function updatePasswordHash(int $mailUserId, string $passwordHash): void
    {
        $this->mailConnection->executeStatement(
            'UPDATE users SET password = :password WHERE id = :id',
            [
                'password' => $passwordHash,
                'id' => $mailUserId,
            ],
            [
                'password' => ParameterType::STRING,
                'id' => ParameterType::INTEGER,
            ]
        );
    }


    public function listPaged(int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        $total = (int) $this->mailConnection->fetchOne('SELECT COUNT(*) FROM users');

        $queryBuilder = $this->mailConnection->createQueryBuilder();
        $queryBuilder->select('user.id', 'user.domain_id', 'user.email', 'user.active', 'domain.name AS domain_name')
            ->from('users', 'user')
            ->leftJoin('user', 'domains', 'domain', 'domain.id = user.domain_id')
            ->addOrderBy('user.active', 'DESC')
            ->addOrderBy('domain.name', 'ASC')
            ->addOrderBy('user.email', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        
        $rows = $queryBuilder->executeQuery()->fetchAllAssociative();

        return [
            'total' => $total,
            'rows' => $rows
        ];
    }

    public function paginatedByQuery(MailUserSearchQueryDTO $query, ?int $mailDomainId = null): array
    {
        $page = max(1, $query->page);
        $limit = max(1, $query->limit);

        $needle = null;
        if ($query->q !== null && trim($query->q) !== '') {
            $needle = '%' . trim($query->q) . '%';
        }

        $sortMap = [
            'email' => 'user.email',
            'domain' => 'domain.name',
            'active' => 'user.active'
        ];

        $sortField = $sortMap[$query->sort ?? ''] ?? 'user.active';
        $order = strtolower($query->order) === 'asc' ? 'ASC' : 'DESC';

        $applyFilters = function ($queryBuilder) use ($needle, $query, $mailDomainId): void {
            if ($needle !== null) {
                $queryBuilder->andWhere('user.email LIKE :needle')
                    ->setParameter('needle', $needle);
            }

            if ($query->active !== null) {
                $queryBuilder->andWhere('user.active = :active')
                    ->setParameter('active', $query->active ? 1 : 0);
            }

            if ($mailDomainId !== null) {
                $queryBuilder->andWhere('user.domain_id = :domainId')
                    ->setParameter('domainId', $mailDomainId);
            }
        };
        $countQueryBuilder = $this->mailConnection->createQueryBuilder();
        $countQueryBuilder->select('COUNT(*)')
            ->from('users', 'user')
            ->leftJoin('user', 'domains', 'domain', 'domain.id = user.domain_id');
        $applyFilters($countQueryBuilder);

        $total = (int) $countQueryBuilder->executeQuery()->fetchOne();

        $dataQueryBuilder = $this->mailConnection->createQueryBuilder();
        $dataQueryBuilder->select('user.id', 'user.domain_id', 'user.email', 'user.active', 'domain.name AS domain_name')
            ->from('users', 'user')
            ->leftJoin('user', 'domains', 'domain', 'domain.id = user.domain_id');
        $applyFilters($dataQueryBuilder);

        $dataQueryBuilder->addOrderBy($sortField, $order);

        if ($sortField !== 'user.active') {
            $dataQueryBuilder->addOrderBy('user.active', 'DESC');
        }
        if ($sortField !== 'domain.name') {
            $dataQueryBuilder->addOrderBy('domain.name', 'ASC');
        }
        if ($sortField !== 'user.email') {
            $dataQueryBuilder->addOrderBy('user.email', 'ASC');
        }

        $dataQueryBuilder->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $rows = $dataQueryBuilder->executeQuery()->fetchAllAssociative();

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'perPage' => $limit,
            'sort' => $sortField,
            'order' => $order,
        ];
    }
}

?>