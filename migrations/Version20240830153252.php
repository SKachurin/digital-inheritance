<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240830153252 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE verification_token DROP FOREIGN KEY FK_C1CC006B9395C3F3');
        $this->addSql('DROP INDEX IDX_C1CC006B9395C3F3 ON verification_token');
        $this->addSql('ALTER TABLE verification_token DROP customer_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE verification_token ADD customer_id INT NOT NULL');
        $this->addSql('ALTER TABLE verification_token ADD CONSTRAINT FK_C1CC006B9395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_C1CC006B9395C3F3 ON verification_token (customer_id)');
    }
}
