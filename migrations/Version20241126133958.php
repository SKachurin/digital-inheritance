<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241126133958 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE beneficiary ADD customer_id INT NOT NULL');
        $this->addSql('ALTER TABLE beneficiary ADD CONSTRAINT FK_7ABF446A9395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('CREATE INDEX IDX_7ABF446A9395C3F3 ON beneficiary (customer_id)');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA149395C3F3');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA149395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA149395C3F3');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA149395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE beneficiary DROP FOREIGN KEY FK_7ABF446A9395C3F3');
        $this->addSql('DROP INDEX IDX_7ABF446A9395C3F3 ON beneficiary');
        $this->addSql('ALTER TABLE beneficiary DROP customer_id');
    }
}
