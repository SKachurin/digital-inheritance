<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251114120849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE note ADD customer_text_answer_one_kms2 LONGTEXT DEFAULT NULL, ADD customer_text_answer_one_kms3 LONGTEXT DEFAULT NULL, ADD customer_text_answer_two_kms2 LONGTEXT DEFAULT NULL, ADD customer_text_answer_two_kms3 LONGTEXT DEFAULT NULL, ADD beneficiary_text_answer_one_kms2 LONGTEXT DEFAULT NULL, ADD beneficiary_text_answer_one_kms3 LONGTEXT DEFAULT NULL, ADD beneficiary_text_answer_two_kms2 LONGTEXT DEFAULT NULL, ADD beneficiary_text_answer_two_kms3 LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE note DROP customer_text_answer_one_kms2, DROP customer_text_answer_one_kms3, DROP customer_text_answer_two_kms2, DROP customer_text_answer_two_kms3, DROP beneficiary_text_answer_one_kms2, DROP beneficiary_text_answer_one_kms3, DROP beneficiary_text_answer_two_kms2, DROP beneficiary_text_answer_two_kms3');
    }
}
