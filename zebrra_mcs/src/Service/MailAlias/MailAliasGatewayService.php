<?php

namespace App\Service\MailAlias;

use Doctrine\DBAL\Connection;

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
}

?>