<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed kms2 row (and keep gateway_ids consistent)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "INSERT IGNORE INTO kms (alias, gateway_ids, last_health, check_date, created_at, updated_at)
                VALUES ('kms2', '[\"API_HEALTHCHECK_IP_1\"]', NULL, NULL, NOW(), NOW())"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM kms WHERE alias = 'kms2'");
    }
}