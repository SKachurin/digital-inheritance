<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223161000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Introduce Oracle kms3 and enable mirror gateway failover for kms1, kms2, kms3';
    }

    public function up(Schema $schema): void
    {
        // 1. Ensure kms3 exists (Oracle KMS replica)
        $this->addSql("INSERT IGNORE INTO kms (alias, gateway_ids, last_health, check_date, created_at, updated_at)
             VALUES ('kms3', '[\"API_HEALTHCHECK_IP_1\",\"API_HEALTHCHECK_IP_2\"]', NULL, NULL, NOW(), NOW())"
        );

        // 2. Update all KMS rows to support both gateways
        $this->addSql(
            "UPDATE kms
             SET gateway_ids = '[\"API_HEALTHCHECK_IP_1\",\"API_HEALTHCHECK_IP_2\"]',
                 updated_at = NOW()
             WHERE alias IN ('kms1','kms2','kms3')"
        );
    }

    public function down(Schema $schema): void
    {
        // revert gateway setup to single gateway
        $this->addSql(
            "UPDATE kms
             SET gateway_ids = '[\"API_HEALTHCHECK_IP_1\"]',
                 updated_at = NOW()
             WHERE alias IN ('kms1','kms2','kms3')"
        );

        // remove kms3
        $this->addSql(
            "DELETE FROM kms WHERE alias = 'kms3'"
        );

    }
}