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

namespace OpenDxp\Bundle\AdminBundle\DependencyInjection\Compiler;

use OpenDxp\Bundle\AdminBundle\Security\ContentSecurityPolicyHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class ContentSecurityPolicyUrlsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(ContentSecurityPolicyHandler::class);

        $config = $container->getParameter('opendxp_admin.config');

        foreach ($config['admin_csp_header']['additional_urls'] as $additionalUrlsKey => $additionalUrlsArr) {
            $definition->addMethodCall('addAllowedUrls', [$additionalUrlsKey, $additionalUrlsArr]);
        }
    }
}
