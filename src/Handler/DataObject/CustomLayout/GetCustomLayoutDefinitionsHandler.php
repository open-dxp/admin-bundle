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

use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\GetCustomLayoutDefinitions\GetCustomLayoutDefinitionsPayload;
use OpenDxp\Model\DataObject;

final class GetCustomLayoutDefinitionsHandler
{
    public function __invoke(GetCustomLayoutDefinitionsPayload $payload): CustomLayoutDefinitionsResult
    {
        $classIds = explode(',', $payload->classId);
        $list = new DataObject\ClassDefinition\CustomLayout\Listing();
        $list->setFilter(
            fn (DataObject\ClassDefinition\CustomLayout $layout) => in_array($layout->getClassId(), $classIds) && !str_contains($layout->getId(), '.brick.')
        );

        $definitions = [];
        foreach ($list->load() as $item) {
            $definitions[] = [
                'id' => $item->getId(),
                'name' => $item->getName(),
                'default' => $item->getDefault(),
            ];
        }

        return new CustomLayoutDefinitionsResult($definitions);
    }
}
