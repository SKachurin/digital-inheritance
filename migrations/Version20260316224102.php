<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260316224102 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action ADD attempt_count INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE kms RENAME INDEX alias TO UNIQ_A29CC8BFE16C6B94');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action DROP attempt_count');
        $this->addSql('ALTER TABLE kms RENAME INDEX UNIQ_A29CC8BFE16C6B94 TO alias');
    }
}
