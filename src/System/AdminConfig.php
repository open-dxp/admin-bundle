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

namespace OpenDxp\Bundle\AdminBundle\System;

use OpenDxp;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Config\LocationAwareConfigRepository;
use OpenDxp\Helper\SystemConfig;

/**
 * @internal
 */
final class AdminConfig
{
    private const string CONFIG_ID = 'admin_system_settings';

    private const string BRANDING = 'branding';

    private const string ASSETS = 'assets';

    private const string SCOPE = 'opendxp_admin_system_settings';

    private static ?LocationAwareConfigRepository $locationAwareConfigRepository = null;

    private static function getRepository(): LocationAwareConfigRepository
    {
        if (!self::$locationAwareConfigRepository) {
            $containerConfig = OpenDxp::getContainer()->getParameter('opendxp_admin.config');
            $config[self::CONFIG_ID][self::BRANDING] = $containerConfig[self::BRANDING];
            $config[self::CONFIG_ID][self::ASSETS] = $containerConfig[self::ASSETS];
            $storageConfig = $containerConfig['config_location'][self::CONFIG_ID];

            self::$locationAwareConfigRepository = new LocationAwareConfigRepository(
                $config,
                self::SCOPE,
                $storageConfig
            );
        }

        return self::$locationAwareConfigRepository;
    }

    public static function get(): array
    {
        $repository = self::getRepository();

        $data = SystemConfig::getConfigDataByKey($repository, self::CONFIG_ID);
        $loadType = $repository->getReadTargets()[0] ?? null;

        // If the read target is settings-store and no data is found there,
        // load the data from the container config
        if (!$data && $loadType === \OpenDxp\Config\LocationAwareConfigRepository::LOCATION_SETTINGS_STORE) {
            $containerConfig = OpenDxp::getContainer()->getParameter('opendxp_admin.config');
            $data[self::BRANDING] = $containerConfig[self::BRANDING];
            $data[self::ASSETS] = $containerConfig[self::ASSETS];
            $data['writeable'] = $repository->isWriteable();
        }

        return $data;
    }

    public function save(array $values): void
    {
        $repository = self::getRepository();

        $data[self::BRANDING] = [
            'login_screen_invert_colors' => $values['branding.login_screen_invert_colors'],
            'color_login_screen' => $values['branding.color_login_screen'],
            'color_admin_interface' => $values['branding.color_admin_interface'],
            'color_admin_interface_background' => $values['branding.color_admin_interface_background'],
            'login_screen_custom_image' => str_replace('%', '%%', $values['branding.login_screen_custom_image']),
        ];

        $data[self::ASSETS] = [
            'hide_edit_image' => $values['assets.hide_edit_image'],
            'disable_tree_preview' => $values['assets.disable_tree_preview'],
        ];

        $repository->saveConfig(self::CONFIG_ID, $data, fn ($key, $data) => [
            'opendxp_admin' => $data,
        ]);
    }

    /**
     * @internal
     */
    public function getAdminSystemSettingsConfig(): array
    {
        if (RuntimeCache::isRegistered('opendxp_admin_system_settings_config')) {
            $config = RuntimeCache::get('opendxp_admin_system_settings_config');
        } else {
            $config = self::get();
            $this->setAdminSystemSettingsConfig($config);
        }

        return $config;
    }

    /**
     * @internal
     */
    public function setAdminSystemSettingsConfig(array $config): void
    {
        RuntimeCache::set('opendxp_admin_system_settings_config', $config);
    }
}
