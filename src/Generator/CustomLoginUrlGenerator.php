<?php

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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Generator;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CustomLoginUrlGenerator
{
    public function __construct(
        private readonly UrlGeneratorInterface $router,
        #[Autowire('%opendxp_admin.custom_admin_route_name%')]
        private readonly string $customAdminRouteName,
    ) {}

    public function generate(array $params, string $fallbackRoute = 'opendxp_admin_login_check'): string
    {
        try {
            return $this->router->generate($this->customAdminRouteName, $params, UrlGeneratorInterface::ABSOLUTE_URL);
        } catch (\Exception) {
            return $this->router->generate($fallbackRoute, $params, UrlGeneratorInterface::ABSOLUTE_URL);
        }
    }
}
