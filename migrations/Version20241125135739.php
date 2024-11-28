<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241125135739 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14ECCAAFA0');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14ECCAAFA0 FOREIGN KEY (beneficiary_id) REFERENCES beneficiary (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14ECCAAFA0');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14ECCAAFA0 FOREIGN KEY (beneficiary_id) REFERENCES beneficiary (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}