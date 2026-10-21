<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1792540800CreatePriceZone extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1792540800;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `limasoft_price_zone` (
                `id` BINARY(16) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `rule_id` BINARY(16) NULL,
                `priority` INT(11) NOT NULL DEFAULT 0,
                `serviceable` TINYINT(1) NOT NULL DEFAULT 1,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                KEY `idx.limasoft_price_zone.lookup` (`active`, `priority`),
                CONSTRAINT `fk.limasoft_price_zone.rule_id` FOREIGN KEY (`rule_id`)
                    REFERENCES `rule` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
        ');

        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `limasoft_price_zone_postcode` (
                `id` BINARY(16) NOT NULL,
                `zone_id` BINARY(16) NOT NULL,
                `country_iso` VARCHAR(2) NOT NULL DEFAULT \'DE\',
                `pattern` VARCHAR(16) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.limasoft_price_zone_postcode.pattern` (`zone_id`, `country_iso`, `pattern`),
                KEY `idx.limasoft_price_zone_postcode.country` (`country_iso`),
                CONSTRAINT `fk.limasoft_price_zone_postcode.zone_id` FOREIGN KEY (`zone_id`)
                    REFERENCES `limasoft_price_zone` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
