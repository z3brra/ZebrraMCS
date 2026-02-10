<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210204102 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mail_alias_links (id INT AUTO_INCREMENT NOT NULL, uuid VARCHAR(36) NOT NULL, mailAliasId INT NOT NULL, createdAt DATETIME NOT NULL, UNIQUE INDEX UNIQ_MAIL_ALIAS_LINK_UUID (uuid), UNIQUE INDEX UNIQ_MAIL_ALIAS_LINK_MAIL_ALIAS_ID (mailAliasId), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE mail_alias_links');
    }
}
