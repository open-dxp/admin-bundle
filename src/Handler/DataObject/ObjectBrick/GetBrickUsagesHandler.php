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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick;

use OpenDxp\Model\DataObject;

final class GetBrickUsagesHandler
{
    public function __invoke(string $classId): BrickUsagesResult
    {
        $myClass = DataObject\ClassDefinition::getById($classId);
        $usages = [];

        foreach ((new DataObject\Objectbrick\Definition\Listing())->load() as $brickDefinition) {
            foreach ($brickDefinition->getClassDefinitions() as $class) {
                if ($myClass->getName() == $class['classname']) {
                    $usages[] = [
                        'objectbrick' => $brickDefinition->getKey(),
                        'field' => $class['fieldname'],
                    ];
                }
            }
        }

        return new BrickUsagesResult($usages);
    }
}
