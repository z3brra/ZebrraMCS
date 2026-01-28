<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260128093806 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mail_domain_links (id INT AUTO_INCREMENT NOT NULL, uuid VARCHAR(36) NOT NULL, mailDomainId INT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_MAIL_DOMAIN_LINKS_UUID (uuid), UNIQUE INDEX UNIQ_MAIL_DOMAIN_LINKS_MAIL_DOMAIN_ID (mailDomainId), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE mail_domain_links');
    }
}
