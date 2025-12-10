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

namespace OpenDxp\Bundle\AdminBundle\Helper;

use OpenDxp;
use OpenDxp\Security\User\User as UserProxy;

/**
 * @internal
 */
final class User
{
    protected const string DEFAULT_KEY_BINDINGS = 'default_key_bindings';

    /**
     * @internal
     */
    public static function getDefaultKeyBindings(OpenDxp\Model\User|UserProxy|null $user = null): string
    {
        if ($user instanceof OpenDxp\Model\User && $user->getKeyBindings()) {
            return $user->getKeyBindings();
        }

        $defaultKeyBindings = [];
        $container = OpenDxp::getContainer();
        /** @var array|null $userConfig */
        $userConfig = $container->getParameter('opendxp_admin.user');
        // make sure the default key binding node is in the config
        if (is_array($userConfig) && array_key_exists(self::DEFAULT_KEY_BINDINGS, $userConfig)) {
            $defaultKeyBindingsConfig = $userConfig[self::DEFAULT_KEY_BINDINGS];
            if (!empty($defaultKeyBindingsConfig)) {
                foreach ($defaultKeyBindingsConfig as $keys) {
                    $defaultKeyBinding = [];
                    // we do not check if the keys are empty because key is required
                    foreach ($keys as $index => $value) {
                        if ($index === 'key') {
                            $value = ord($value);
                        }
                        $defaultKeyBinding[$index] = $value;
                    }
                    $defaultKeyBindings[] = $defaultKeyBinding;
                }
            }
        }

        return json_encode($defaultKeyBindings);
    }
}
