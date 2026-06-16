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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout;

use OpenDxp\Model\DataObject;

final class GetAllLayoutsHandler
{
    public function __invoke(): AllLayoutsResult
    {
        $mapping = [];
        $customLayouts = new DataObject\ClassDefinition\CustomLayout\Listing();
        $customLayouts->setFilter(fn (DataObject\ClassDefinition\CustomLayout $layout) => !str_contains($layout->getId(), '.brick.'));
        $customLayouts->setOrder(fn (DataObject\ClassDefinition\CustomLayout $a, DataObject\ClassDefinition\CustomLayout $b) => strcmp($a->getName(), $b->getName()));

        foreach ($customLayouts->load() as $layout) {
            $mapping[$layout->getClassId()][] = $layout;
        }

        $classList = new DataObject\ClassDefinition\Listing();
        $classList->setOrder('ASC');
        $classList->setOrderKey('name');

        $layouts = [];
        foreach ($classList->load() as $class) {
            if (!isset($mapping[$class->getId()])) {
                continue;
            }
            $layouts[] = [
                'type' => 'main',
                'id' => $class->getId() . '_' . 0,
                'name' => $class->getName(),
            ];
            foreach ($mapping[$class->getId()] as $layout) {
                $layouts[] = [
                    'type' => 'custom',
                    'id' => $class->getId() . '_' . $layout->getId(),
                    'name' => $class->getName() . ' - ' . $layout->getName(),
                ];
            }
        }

        return new AllLayoutsResult($layouts);
    }
}
