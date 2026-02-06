<?php

namespace App\Service\MailUser;

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
}

?>