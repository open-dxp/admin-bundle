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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\ExportClass;

use OpenDxp\Logger;
use OpenDxp\Model\DataObject;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ExportClassHandler
{
    public function __invoke(ExportClassPayload $payload): ExportClassResult
    {
        $class = DataObject\ClassDefinition::getById($payload->id);
        if (!$class instanceof DataObject\ClassDefinition) {
            $errorMessage = ': Class with id [ ' . $payload->id . ' not found. ]';
            Logger::error($errorMessage);

            throw new NotFoundHttpException($errorMessage);
        }

        $json = DataObject\ClassDefinition\Service::generateClassDefinitionJson($class);

        return new ExportClassResult(
            json: $json,
            className: $class->getName() ?? '',
        );
    }
}
