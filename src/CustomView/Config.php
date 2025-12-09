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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\CustomView;

use Exception;
use OpenDxp;
use OpenDxp\Config\LocationAwareConfigRepository;

/**
 * @internal
 */
final class Config
{
    private const string CONFIG_ID = 'custom_views';

    private static ?LocationAwareConfigRepository $locationAwareConfigRepository = null;

    private static function getRepository(): LocationAwareConfigRepository
    {
        if (!self::$locationAwareConfigRepository) {
            $containerConfig = OpenDxp::getContainer()->getParameter('opendxp.config');
            $config = $containerConfig[self::CONFIG_ID]['definitions'];

            $storageConfig = $containerConfig['config_location'][self::CONFIG_ID];

            self::$locationAwareConfigRepository = new LocationAwareConfigRepository(
                $config,
                'opendxp_custom_views',
                $storageConfig
            );
        }

        return self::$locationAwareConfigRepository;
    }

    protected static function flipArray(mixed $data): array
    {
        if (empty($data['classes'])) {
            return [];
        }
        $tempClasses = explode(',', $data['classes']);

        return array_fill_keys($tempClasses, null);
    }

    /**
     * @internal
     */
    public static function get(): array
    {
        $config = [];
        $repository = self::getRepository();
        $keys = $repository->fetchAllKeys();
        foreach ($keys as $key) {
            [$data, $dataSource] = $repository->loadConfigByKey(($key));
            $data['writeable'] = $repository->isWriteable($key, $dataSource);
            $data['id'] ??= $key;
            if (!is_array($data['classes'] ?? [])) {
                $data['classes'] = self::flipArray($data);
            }

            $config[$data['id']] = $data;
        }

        return $config;
    }

    /**
     * @throws Exception
     */
    public static function save(array $data, ?array $deletedRecords): void
    {
        $repository = self::getRepository();

        foreach ($data as $key => $value) {
            $key = (string) $key;
            [$configKey, $dataSource] = $repository->loadConfigByKey($key);
            if ($repository->isWriteable($key, $dataSource)) {
                unset($value['writeable']);
                $repository->saveConfig($key, $value, fn ($key, $data) => [
                    'opendxp' => [
                        'custom_views' => [
                            'definitions' => [
                                $key => $data,
                            ],
                        ],
                    ],
                ]);
            }
        }

        if ($deletedRecords) {
            foreach ($deletedRecords as $key) {
                [$configKey, $dataSource] = $repository->loadConfigByKey(($key));
                if (!empty($configKey)) {
                    $repository->deleteData($key, $dataSource);
                }
            }
        }
    }

    public static function isWriteable(): bool
    {
        return self::getRepository()->isWriteable();
    }
}
