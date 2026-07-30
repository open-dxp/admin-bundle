<?php

declare(strict_types=1);

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use OpenDxp\Bundle\AdminBundle\Security\AdminPermission;
use OpenDxp\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use OpenDxp\Security\PermissionAttribute;
use Override;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class Installer extends SettingsStoreAwareInstaller
{
    protected const string USER_PERMISSIONS_CATEGORY = 'OpenDxp Admin Bundle';

    private array $tablesToInstall = [
        'translations_admin' =>
            "CREATE TABLE `translations_admin` (
              `key` varchar(190) NOT NULL DEFAULT '' COLLATE 'utf8mb4_bin',
              `type` varchar(10) DEFAULT NULL,
              `language` varchar(10) NOT NULL DEFAULT '',
              `text` text,
              `creationDate` int(11) unsigned DEFAULT NULL,
              `modificationDate` int(11) unsigned DEFAULT NULL,
              `userOwner` int(11) unsigned DEFAULT NULL,
              `userModification` int(11) unsigned DEFAULT NULL,
              PRIMARY KEY (`key`,`language`),
              KEY `language` (`language`)
            ) DEFAULT CHARSET=utf8mb4;",
    ];

    protected ?Schema $schema = null;

    public function __construct(
        protected BundleInterface $bundle,
        protected Connection $db
    ) {
        parent::__construct($bundle);
    }

    protected function addPermissions(): void
    {
        $db = \OpenDxp\Db::get();

        $existingKeys = $db->fetchFirstColumn(sprintf('SELECT %s FROM users_permission_definitions', $db->quoteIdentifier('key')));

        foreach (AdminPermission::cases() as $permission) {
            if (in_array(PermissionAttribute::for($permission->value), $existingKeys, true)) {
                continue;
            }

            $db->insert('users_permission_definitions', [
                $db->quoteIdentifier('key') => PermissionAttribute::for($permission->value),
                $db->quoteIdentifier('category') => self::USER_PERMISSIONS_CATEGORY,
            ]);
        }
    }

    protected function removePermissions(): void
    {
        $db = \OpenDxp\Db::get();

        foreach (AdminPermission::cases() as $permission) {
            $db->delete('users_permission_definitions', [
                $db->quoteIdentifier('key') => PermissionAttribute::for($permission->value),
            ]);
        }
    }

    #[Override]
    public function install(): void
    {
        $this->addPermissions();
        $this->installTables();
        parent::install();
    }

    private function installTables(): void
    {
        foreach ($this->tablesToInstall as $name => $statement) {
            if ($this->getSchema()->hasTable($name)) {
                $this->output->write(sprintf(
                    '     <comment>WARNING:</comment> Skipping table "%s" as it already exists',
                    $name
                ));

                continue;
            }

            $this->db->executeStatement($statement);
        }
    }

    private function uninstallTables(): void
    {
        foreach (array_keys($this->tablesToInstall) as $table) {
            if (!$this->getSchema()->hasTable($table)) {
                $this->output->write(sprintf(
                    '     <comment>WARNING:</comment> Not dropping table "%s" as it doesn\'t exist',
                    $table
                ));

                continue;
            }

            $this->db->executeStatement(sprintf('DROP TABLE IF EXISTS %s', $this->db->quoteIdentifier($table)));
        }
    }

    #[Override]
    public function uninstall(): void
    {
        $this->removePermissions();
        $this->uninstallTables();

        parent::uninstall();
    }

    protected function getSchema(): Schema
    {
        return $this->schema ??= $this->db->createSchemaManager()->introspectSchema();
    }
}
