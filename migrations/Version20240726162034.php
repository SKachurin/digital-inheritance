<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240726162034 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE action (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, note_id INT NOT NULL, action_type VARCHAR(255) NOT NULL, `interval` VARCHAR(255) NOT NULL, status VARCHAR(64) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_47CC8C929395C3F3 (customer_id), INDEX IDX_47CC8C9226ED0855 (note_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE beneficiary (id INT AUTO_INCREMENT NOT NULL, beneficiary_name VARCHAR(64) DEFAULT NULL, beneficiary_full_name VARCHAR(150) DEFAULT NULL, beneficiary_first_question VARCHAR(200) DEFAULT NULL, beneficiary_first_question_answer VARCHAR(64) DEFAULT NULL, beneficiary_second_question VARCHAR(200) DEFAULT NULL, beneficiary_second_question_answer VARCHAR(64) DEFAULT NULL, beneficiary_actions_order LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contact (id INT AUTO_INCREMENT NOT NULL, customer_id INT DEFAULT NULL, beneficiary_id INT DEFAULT NULL, contact_type_enum VARCHAR(64) NOT NULL, country_code VARCHAR(64) DEFAULT NULL, value VARCHAR(150) NOT NULL, is_verified TINYINT(1) NOT NULL, INDEX IDX_4C62E6389395C3F3 (customer_id), INDEX IDX_4C62E638ECCAAFA0 (beneficiary_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE customer (id INT AUTO_INCREMENT NOT NULL, customer_name VARCHAR(64) DEFAULT NULL, customer_email VARCHAR(64) NOT NULL, customer_full_name VARCHAR(150) DEFAULT NULL, customer_first_question VARCHAR(200) DEFAULT NULL, customer_first_question_answer VARCHAR(64) DEFAULT NULL, customer_second_question VARCHAR(200) DEFAULT NULL, customer_second_question_answer VARCHAR(64) DEFAULT NULL, customer_social_app VARCHAR(255) NOT NULL, customer_payment_status VARCHAR(255) NOT NULL, customer_okay_password VARCHAR(200) DEFAULT NULL, password VARCHAR(200) DEFAULT NULL, customer_actions_order LONGTEXT DEFAULT NULL, roles JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_81398E0929A7094F (customer_email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE note (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, beneficiary_id INT DEFAULT NULL, customer_text_answer_one LONGTEXT DEFAULT NULL, customer_text_answer_two LONGTEXT DEFAULT NULL, beneficiary_text_answer_one LONGTEXT DEFAULT NULL, beneficiary_text_answer_two LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_CFBDFA149395C3F3 (customer_id), INDEX IDX_CFBDFA14ECCAAFA0 (beneficiary_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pipeline (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, note_id INT DEFAULT NULL, action_status VARCHAR(255) NOT NULL, action_type VARCHAR(255) NOT NULL, pipeline_status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7DFCD9D99395C3F3 (customer_id), UNIQUE INDEX UNIQ_7DFCD9D926ED0855 (note_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transaction (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, amount DOUBLE PRECISION DEFAULT NULL, currency VARCHAR(64) DEFAULT NULL, payment_method VARCHAR(100) DEFAULT NULL, status VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_723705D19395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE verification_token (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, token VARCHAR(255) NOT NULL, type VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_C1CC006B5F37A13B (token), INDEX IDX_C1CC006B9395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE action ADD CONSTRAINT FK_47CC8C929395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE action ADD CONSTRAINT FK_47CC8C9226ED0855 FOREIGN KEY (note_id) REFERENCES note (id)');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E6389395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E638ECCAAFA0 FOREIGN KEY (beneficiary_id) REFERENCES beneficiary (id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA149395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14ECCAAFA0 FOREIGN KEY (beneficiary_id) REFERENCES beneficiary (id)');
        $this->addSql('ALTER TABLE pipeline ADD CONSTRAINT FK_7DFCD9D99395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE pipeline ADD CONSTRAINT FK_7DFCD9D926ED0855 FOREIGN KEY (note_id) REFERENCES note (id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D19395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE verification_token ADD CONSTRAINT FK_C1CC006B9395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action DROP FOREIGN KEY FK_47CC8C929395C3F3');
        $this->addSql('ALTER TABLE action DROP FOREIGN KEY FK_47CC8C9226ED0855');
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E6389395C3F3');
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E638ECCAAFA0');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA149395C3F3');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14ECCAAFA0');
        $this->addSql('ALTER TABLE pipeline DROP FOREIGN KEY FK_7DFCD9D99395C3F3');
        $this->addSql('ALTER TABLE pipeline DROP FOREIGN KEY FK_7DFCD9D926ED0855');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D19395C3F3');
        $this->addSql('ALTER TABLE verification_token DROP FOREIGN KEY FK_C1CC006B9395C3F3');
        $this->addSql('DROP TABLE action');
        $this->addSql('DROP TABLE beneficiary');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE note');
        $this->addSql('DROP TABLE pipeline');
        $this->addSql('DROP TABLE transaction');
        $this->addSql('DROP TABLE verification_token');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
