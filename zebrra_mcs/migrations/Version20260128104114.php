<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260128104114 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE api_token_scopes CHANGE domainId domainUuid INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (token_id, domainUuid)');
        $this->addSql('CREATE INDEX IDX_API_TOKEN_SCOPES_DOMAIN_UUID ON api_token_scopes (domainUuid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_API_TOKEN_SCOPES_DOMAIN_UUID ON api_token_scopes');
        $this->addSql('ALTER TABLE api_token_scopes CHANGE domainUuid domainId INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (token_id, domainId)');
    }
}
