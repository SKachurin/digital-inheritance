<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241014172101 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pipeline DROP FOREIGN KEY FK_7DFCD9D926ED0855');
        $this->addSql('DROP INDEX UNIQ_7DFCD9D926ED0855 ON pipeline');
        $this->addSql('ALTER TABLE pipeline ADD action_sequence JSON NOT NULL, DROP note_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pipeline ADD note_id INT DEFAULT NULL, DROP action_sequence');
        $this->addSql('ALTER TABLE pipeline ADD CONSTRAINT FK_7DFCD9D926ED0855 FOREIGN KEY (note_id) REFERENCES note (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7DFCD9D926ED0855 ON pipeline (note_id)');
    }
}
