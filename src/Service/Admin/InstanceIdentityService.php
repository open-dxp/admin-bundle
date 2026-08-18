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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Service\Admin;

use Exception;
use OpenDxp\Model\Tool\SettingsStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

/**
 * Provides identity values for this OpenDXP installation
 *
 * @internal
 */
final class InstanceIdentityService
{
    private const string SETTINGS_STORE_KEY = 'system_uuid';

    private const string SETTINGS_STORE_SCOPE = 'opendxp';

    public function __construct(
        #[Autowire('%secret%')]
        private readonly string $secret,
    ) {
    }

    public function getInstanceId(): string
    {
        try {
            return sha1(substr($this->secret, 3, -3));
        } catch (Exception) {
            return 'not-set';
        }
    }

    public function getSystemUuid(string $environment): string
    {
        try {
            $rootUuid = $this->getOrCreateRootUuid();

            return Uuid::v5(Uuid::fromString($rootUuid), $environment)->toRfc4122();
        } catch (Exception) {
            // fall back to a random, non-colliding, non-persisted value
            return bin2hex(random_bytes(16));
        }
    }

    /**
     * @throws Exception
     */
    private function getOrCreateRootUuid(): string
    {
        $existing = SettingsStore::get(self::SETTINGS_STORE_KEY, self::SETTINGS_STORE_SCOPE);
        if ($existing !== null) {
            return (string) $existing->getData();
        }

        $rootUuid = Uuid::v4()->toRfc4122();
        SettingsStore::set(self::SETTINGS_STORE_KEY, $rootUuid, SettingsStore::TYPE_STRING, self::SETTINGS_STORE_SCOPE);

        return $rootUuid;
    }
}
