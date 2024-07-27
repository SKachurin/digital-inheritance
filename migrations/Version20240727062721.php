<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240727062721 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer CHANGE customer_full_name customer_full_name VARCHAR(512) DEFAULT NULL, CHANGE customer_first_question customer_first_question VARCHAR(1024) DEFAULT NULL, CHANGE customer_first_question_answer customer_first_question_answer VARCHAR(512) DEFAULT NULL, CHANGE customer_second_question customer_second_question VARCHAR(1024) DEFAULT NULL, CHANGE customer_second_question_answer customer_second_question_answer VARCHAR(512) DEFAULT NULL, CHANGE customer_okay_password customer_okay_password VARCHAR(512) DEFAULT NULL, CHANGE password password VARCHAR(512) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer CHANGE customer_full_name customer_full_name VARCHAR(150) DEFAULT NULL, CHANGE customer_first_question customer_first_question VARCHAR(200) DEFAULT NULL, CHANGE customer_first_question_answer customer_first_question_answer VARCHAR(64) DEFAULT NULL, CHANGE customer_second_question customer_second_question VARCHAR(200) DEFAULT NULL, CHANGE customer_second_question_answer customer_second_question_answer VARCHAR(64) DEFAULT NULL, CHANGE customer_okay_password customer_okay_password VARCHAR(200) DEFAULT NULL, CHANGE password password VARCHAR(200) DEFAULT NULL');
    }
}
