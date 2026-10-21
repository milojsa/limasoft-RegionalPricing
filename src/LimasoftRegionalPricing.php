<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class LimasoftRegionalPricing extends Plugin
{
    public function uninstall(UninstallContext $context): void
    {
        parent::uninstall($context);

        if ($context->keepUserData()) {
            return;
        }

        $connection = $this->container->get(Connection::class);

        // the generated zone rules are ours, so they go with the tables
        $ruleIds = $connection->fetchFirstColumn(
            'SELECT `rule_id` FROM `limasoft_price_zone` WHERE `rule_id` IS NOT NULL'
        );

        $connection->executeStatement('DROP TABLE IF EXISTS `limasoft_price_zone_postcode`');
        $connection->executeStatement('DROP TABLE IF EXISTS `limasoft_price_zone`');

        if ($ruleIds === []) {
            return;
        }

        $connection->executeStatement(
            'DELETE FROM `rule` WHERE `id` IN (:ids)',
            ['ids' => $ruleIds],
            ['ids' => ArrayParameterType::BINARY]
        );
    }
}
