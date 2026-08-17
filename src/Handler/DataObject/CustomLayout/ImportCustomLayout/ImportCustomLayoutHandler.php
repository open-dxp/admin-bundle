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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\ImportCustomLayout;

use Exception;
use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Logger;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Exception\ConfigWriteException;

final class ImportCustomLayoutHandler
{
    public function __invoke(ImportCustomLayoutPayload $payload): void
    {
        $importData = $payload->importData;

        if (isset($importData['name'])
            && DataObject\ClassDefinition\CustomLayout::getByName($importData['name']) instanceof DataObject\ClassDefinition\CustomLayout
        ) {
            throw new AdminOperationFailedException('', ['nameAlreadyInUse' => true]);
        }

        $customLayout = DataObject\ClassDefinition\CustomLayout::getById($payload->id);
        if (!$customLayout) {
            throw new AdminOperationFailedException('Custom layout not found');
        }

        try {
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
        } catch (Exception $e) {
            Logger::error($e->getMessage());

            throw new AdminOperationFailedException($e->getMessage());
        }
    }
}
