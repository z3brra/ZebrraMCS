<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260304112147 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE admin_refresh_tokens (id INT AUTO_INCREMENT NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, revoked_at DATETIME DEFAULT NULL, ip VARCHAR(64) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, adminUser_id INT NOT NULL, INDEX IDX_48A4D1C5CC283C73 (adminUser_id), INDEX IDX_ART_EXPIRES (expires_at), INDEX IDX_ART_REVOKED (revoked_at), UNIQUE INDEX UNIQ_ART_TOKEN_HASH (token_hash), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE admin_refresh_tokens ADD CONSTRAINT FK_48A4D1C5CC283C73 FOREIGN KEY (adminUser_id) REFERENCES admin_users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE admin_refresh_tokens DROP FOREIGN KEY FK_48A4D1C5CC283C73');
        $this->addSql('DROP TABLE admin_refresh_tokens');
    }
}
