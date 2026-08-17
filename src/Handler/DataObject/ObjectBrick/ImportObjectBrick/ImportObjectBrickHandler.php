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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\ImportObjectBrick;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\ImportObjectBrick\ImportObjectBrickPayload;
use OpenDxp\Model\DataObject;

final class ImportObjectBrickHandler
{
    public function __invoke(ImportObjectBrickPayload $payload): void
    {
        $objectBrick = DataObject\Objectbrick\Definition::getByKey($payload->id);

        if (!DataObject\ClassDefinition\Service::importObjectBrickFromJson($objectBrick, $payload->json)) {
            throw new AdminOperationFailedException('Failed to import objectbrick: ' . $payload->id);
        }
    }
}
