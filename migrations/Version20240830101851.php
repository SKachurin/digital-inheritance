<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240830101851 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE beneficiary CHANGE beneficiary_name beneficiary_name VARCHAR(200) DEFAULT NULL, CHANGE beneficiary_full_name beneficiary_full_name VARCHAR(512) DEFAULT NULL, CHANGE beneficiary_first_question beneficiary_first_question VARCHAR(1024) DEFAULT NULL, CHANGE beneficiary_first_question_answer beneficiary_first_question_answer VARCHAR(512) DEFAULT NULL, CHANGE beneficiary_second_question beneficiary_second_question VARCHAR(1024) DEFAULT NULL, CHANGE beneficiary_second_question_answer beneficiary_second_question_answer VARCHAR(512) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE beneficiary CHANGE beneficiary_name beneficiary_name VARCHAR(64) DEFAULT NULL, CHANGE beneficiary_full_name beneficiary_full_name VARCHAR(150) DEFAULT NULL, CHANGE beneficiary_first_question beneficiary_first_question VARCHAR(200) DEFAULT NULL, CHANGE beneficiary_first_question_answer beneficiary_first_question_answer VARCHAR(64) DEFAULT NULL, CHANGE beneficiary_second_question beneficiary_second_question VARCHAR(200) DEFAULT NULL, CHANGE beneficiary_second_question_answer beneficiary_second_question_answer VARCHAR(64) DEFAULT NULL');
    }
}
