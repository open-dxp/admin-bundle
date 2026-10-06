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

namespace OpenDxp\Bundle\AdminBundle\Service\DataObject;

use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\ClassDefinition\Data;

final class ContextFieldDefinitionResolver
{
    /**
     * @param array<string, mixed> $context
     */
    public function resolve(DataObject\Concrete $object, array $context): ?Data
    {
        $fieldname = $context['fieldname'] ?? null;

        if (!is_string($fieldname)) {
            return null;
        }

        $class = $object->getClass();
        $containerKey = $context['containerKey'] ?? null;

        return match ($context['containerType'] ?? null) {
            'object' => isset($context['subContainerType'])
                ? $this->child($class->getFieldDefinition($context['subContainerKey'] ?? ''), $fieldname)
                : $this->child($class, $fieldname),
            'localizedfield' => $this->child($class->getFieldDefinition('localizedfields'), $fieldname),
            'objectbrick' => $this->child(DataObject\Objectbrick\Definition::getByKey($containerKey), $fieldname),
            'fieldcollection' => ($context['subContainerType'] ?? null) === 'localizedfield'
                ? $this->child(DataObject\Fieldcollection\Definition::getByKey($containerKey)?->getFieldDefinition('localizedfields'), $fieldname)
                : $this->child(DataObject\Fieldcollection\Definition::getByKey($containerKey), $fieldname),
            default => null,
        };
    }

    private function child(?object $container, string $fieldname): ?Data
    {
        $definition = $container !== null && method_exists($container, 'getFieldDefinition')
            ? $container->getFieldDefinition($fieldname)
            : null;

        return $definition instanceof Data ? $definition : null;
    }
}
