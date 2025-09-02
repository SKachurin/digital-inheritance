<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250830134759 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer ADD invited_by INT DEFAULT NULL, ADD referral_code VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09421FF255 FOREIGN KEY (invited_by) REFERENCES customer (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_81398E096447454A ON customer (referral_code)');
        $this->addSql('CREATE INDEX IDX_81398E09421FF255 ON customer (invited_by)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09421FF255');
        $this->addSql('DROP INDEX UNIQ_81398E096447454A ON customer');
        $this->addSql('DROP INDEX IDX_81398E09421FF255 ON customer');
        $this->addSql('ALTER TABLE customer DROP invited_by, DROP referral_code');
    }
}
