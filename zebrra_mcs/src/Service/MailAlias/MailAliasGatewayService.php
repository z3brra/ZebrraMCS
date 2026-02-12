<?php

namespace App\Service\MailAlias;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final class MailAliasGatewayService
{
    public function __construct(
        private readonly Connection $mailConnection
    ) {}

    public function insert(string $sourceEmail, string $destinationEmail): int
    {
        $this->mailConnection->insert('aliases', [
            'source' => $sourceEmail,
            'destination' => $destinationEmail,
        ]);

        return (int) $this->mailConnection->lastInsertId();
    }

    public function exists(string $sourceEmail, string $destinationEmail): bool
    {
        $id = $this->mailConnection->fetchOne(
            'SELECT id FROM aliases WHERE source = :s AND destination = :d LIMIT 1',
            [
                's' => $sourceEmail,
                'd' => $destinationEmail
            ]
        );

        return $id !== false && $id !== null && $id !== '';
    }

    public function existsById(int $id): bool
    {
        $found = $this->mailConnection->fetchOne(
            'SELECT 1 FROM aliases WHERE id = :id LIMIT 1',
            ['id' => $id],
            ['id' => ParameterType::INTEGER]
        );

        return $found !== false && $found !== null;
    }

    public function deleteById(int $id): int
    {
        return $this->mailConnection->executeStatement(
            'DELETE FROM aliases WHERE id = :id',
            ['id' => $id],
            ['id' => ParameterType::INTEGER]
        );
    }

    public function findByDestinationEmail(string $destinationEmail): array
    {
        $destinationEmail = mb_strtolower(trim($destinationEmail));

        return $this->mailConnection->fetchAllAssociative(
            'SELECT id, source, destination FROM aliases WHERE LOWER(destination) = :dest ORDER BY source ASC',
            ['dest' => $destinationEmail]
        );
    }
}

?>