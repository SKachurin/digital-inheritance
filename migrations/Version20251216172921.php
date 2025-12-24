<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251216172921 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "CREATE TABLE kms (
            id INT AUTO_INCREMENT NOT NULL,
            alias VARCHAR(32) UNIQUE NOT NULL,
            gateway_ids JSON NOT NULL,
            last_health TINYINT(1) DEFAULT NULL,
            check_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB"
        );

        // Seed only kms1 for now
        $this->addSql(
            "INSERT INTO kms (alias, gateway_ids, last_health, check_date, created_at, updated_at)
                VALUES ('kms1', '[\"API_HEALTHCHECK_IP_1\"]', NULL, NULL, NOW(), NOW())"
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE kms');
    }
}
