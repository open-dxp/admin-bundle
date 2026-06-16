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
use OpenDxp\Model\Exception\ConfigWriteException;

final class ImportCustomLayoutHandler
{
    public function __invoke(?string $id, array $importData): void
    {
        $customLayout = DataObject\ClassDefinition\CustomLayout::getById($id);
        if (!$customLayout) {
            return;
        }

        $layout = DataObject\ClassDefinition\Service::generateLayoutTreeFromArray($importData['layoutDefinitions'], true);
        $customLayout->setLayoutDefinitions($layout);
        if (isset($importData['name'])) {
            $customLayout->setName($importData['name']);
        }
        $customLayout->setDescription($importData['description']);

        if (!$customLayout->isWriteable()) {
            throw new ConfigWriteException();
        }

        $customLayout->save();
    }
}
