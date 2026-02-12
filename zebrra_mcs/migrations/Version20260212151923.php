<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260212151923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mail_alias_links ADD sourceEmail VARCHAR(320) NOT NULL, ADD destinationEmail VARCHAR(320) NOT NULL');
        $this->addSql('CREATE INDEX IDX_MAIL_ALIAS_LINK_SOURCE ON mail_alias_links (sourceEmail)');
        $this->addSql('CREATE INDEX IDX_MAIL_ALIAS_LINK_DESTINATION ON mail_alias_links (destinationEmail)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_MAIL_ALIAS_LINK_SOURCE ON mail_alias_links');
        $this->addSql('DROP INDEX IDX_MAIL_ALIAS_LINK_DESTINATION ON mail_alias_links');
        $this->addSql('ALTER TABLE mail_alias_links DROP sourceEmail, DROP destinationEmail');
    }
}
