<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260205155953 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mail_user_links (id INT AUTO_INCREMENT NOT NULL, uuid VARCHAR(36) NOT NULL, mailUserId INT NOT NULL, mailDomainId INT NOT NULL, email VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL, INDEX IDX_MAIL_USER_LINKS_MAIL_DOMAIN_ID (mailDomainId), INDEX IDX_MAIL_USER_LINKS_EMAIL (email), UNIQUE INDEX UNIQ_MAIL_USER_LINKS_UUID (uuid), UNIQUE INDEX UNIQ_MAIL_USER_LINKS_USER_ID (mailUserId), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE mail_user_links');
    }
}
