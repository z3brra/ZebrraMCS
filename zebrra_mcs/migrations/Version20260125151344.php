<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260125151344 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE api_token_permissions (permission VARCHAR(64) NOT NULL, token_id INT NOT NULL, INDEX IDX_6D9895BD41DEE7B9 (token_id), INDEX IDX_API_TOKEN_PERMISSIONS_PERMISSION (permission), PRIMARY KEY (token_id, permission)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE api_token_scopes (domainId INT NOT NULL, token_id INT NOT NULL, INDEX IDX_FC6959F41DEE7B9 (token_id), PRIMARY KEY (token_id, domainId)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE api_tokens (id INT AUTO_INCREMENT NOT NULL, uuid VARCHAR(36) NOT NULL, name VARCHAR(128) NOT NULL, tokenHash VARCHAR(64) NOT NULL, active TINYINT DEFAULT 1 NOT NULL, expiresAt DATETIME DEFAULT NULL, createdAt DATETIME NOT NULL, lastUsedAt DATETIME DEFAULT NULL, revokedAt DATETIME DEFAULT NULL, createdByAdmin_id INT NOT NULL, replacedByToken_id INT DEFAULT NULL, INDEX IDX_2CAD560EC5BC8BF6 (createdByAdmin_id), INDEX IDX_2CAD560E1D930AE2 (replacedByToken_id), INDEX IDX_API_TOKENS_ACTIVE (active), INDEX IDX_API_TOKENS_EXPIRES_AT (expiresAt), INDEX IDX_API_TOKENS_LAST_USED_AT (lastUsedAt), UNIQUE INDEX UNIQ_API_TOKENS_UUID (uuid), UNIQUE INDEX UNIQ_API_TOKENS_TOKEN_HASH (tokenHash), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE api_token_permissions ADD CONSTRAINT FK_6D9895BD41DEE7B9 FOREIGN KEY (token_id) REFERENCES api_tokens (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE api_token_scopes ADD CONSTRAINT FK_FC6959F41DEE7B9 FOREIGN KEY (token_id) REFERENCES api_tokens (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE api_tokens ADD CONSTRAINT FK_2CAD560EC5BC8BF6 FOREIGN KEY (createdByAdmin_id) REFERENCES admin_users (id)');
        $this->addSql('ALTER TABLE api_tokens ADD CONSTRAINT FK_2CAD560E1D930AE2 FOREIGN KEY (replacedByToken_id) REFERENCES api_tokens (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE api_token_permissions DROP FOREIGN KEY FK_6D9895BD41DEE7B9');
        $this->addSql('ALTER TABLE api_token_scopes DROP FOREIGN KEY FK_FC6959F41DEE7B9');
        $this->addSql('ALTER TABLE api_tokens DROP FOREIGN KEY FK_2CAD560EC5BC8BF6');
        $this->addSql('ALTER TABLE api_tokens DROP FOREIGN KEY FK_2CAD560E1D930AE2');
        $this->addSql('DROP TABLE api_token_permissions');
        $this->addSql('DROP TABLE api_token_scopes');
        $this->addSql('DROP TABLE api_tokens');
    }
}
