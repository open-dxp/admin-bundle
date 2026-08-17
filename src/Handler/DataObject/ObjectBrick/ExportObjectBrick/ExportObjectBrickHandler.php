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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\ExportObjectBrick;

use OpenDxp\Logger;
use OpenDxp\Model\DataObject;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ExportObjectBrickHandler
{
    public function __invoke(ExportObjectBrickPayload $payload): ExportObjectBrickResult
    {
        $objectBrick = DataObject\Objectbrick\Definition::getByKey($payload->id);

        if (!$objectBrick instanceof DataObject\Objectbrick\Definition) {
            $errorMessage = ': Object-Brick with id [ ' . $payload->id . ' not found. ]';
            Logger::error($errorMessage);

            throw new NotFoundHttpException($errorMessage);
        }

        return new ExportObjectBrickResult(
            $objectBrick->getKey() ?? '',
            DataObject\ClassDefinition\Service::generateObjectBrickJson($objectBrick),
        );
    }
}
