<?php

namespace App\Service\Domain;

use Doctrine\DBAL\Connection;

final class MailDomainGatewayService
{
    public function __construct(
        private readonly Connection $mailConnection
    ) {}

    /**
     * @return array{id: int, name: string, active: int}|null
     */
    public function findById(int $id): ?array
    {
        $row = $this->mailConnection->fetchAssociative(
            'SELECT id, name, active FROM domains WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
        return $row ?: null;
    }

    /**
     * @return array{id: int, name: string, active: int}|null
     */
    public function findByName(string $name): ?array
    {
        $row = $this->mailConnection->fetchAssociative(
            'SELECT id, name, active FROM domains WHERE name = :name LIMIT 1',
            ['name' => $name]
        );

        return $row ?: null;
    }

    /**
     * @return int newly created mailserver.domains.id
     */
    public function insert(string $name, bool $active): int
    {
        $this->mailConnection->insert('domains', [
            'name' => $name,
            'active' => $active ? 1 : 0,
        ]);

        return (int) $this->mailConnection->lastInsertId();
    }
}

?>