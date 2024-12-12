<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241208161311 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE note ADD attempt_count INT DEFAULT NULL, ADD last_attempt_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD lockout_until DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE verification_token DROP FOREIGN KEY FK_C1CC006BE7A1254A');
        $this->addSql('ALTER TABLE verification_token ADD CONSTRAINT FK_C1CC006BE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE verification_token DROP FOREIGN KEY FK_C1CC006BE7A1254A');
        $this->addSql('ALTER TABLE verification_token ADD CONSTRAINT FK_C1CC006BE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE note DROP attempt_count, DROP last_attempt_at, DROP lockout_until');
    }
}
