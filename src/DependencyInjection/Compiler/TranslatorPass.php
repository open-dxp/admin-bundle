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

use OpenDxp\Translation\Translator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class TranslatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $translationPath = $container->getParameter('opendxp_admin.translations.path');
        $translationMapping = $container->getParameter('opendxp.translations.admin_translation_mapping');
        $container
            ->getDefinition(Translator::class)
            ->addMethodCall('setAdminPath', [$translationPath])
            ->addMethodCall('setAdminTranslationMapping', [$translationMapping]);

        $editableHandlerDefinition = $container->getDefinition(\OpenDxp\Document\Editable\EditableHandler::class);
        $adminUserTranslatorReference = new Reference(\OpenDxp\Bundle\AdminBundle\Translation\AdminUserTranslator::class);
        $editableHandlerDefinition->setArgument('$translator', $adminUserTranslatorReference);
    }
}
