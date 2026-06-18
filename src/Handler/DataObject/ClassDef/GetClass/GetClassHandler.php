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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClass;

use OpenDxp\Model\DataObject;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetClassHandler
{
    public function __invoke(GetClassPayload $payload): GetClassResult
    {
        $class = DataObject\ClassDefinition::getById($payload->id);
        if (!$class) {
            throw new NotFoundHttpException('Class not found');
        }

        $class->setFieldDefinitions([]);
        $isWriteable = $class->isWritable();
        $classData = $class->getObjectVars();
        $classData['isWriteable'] = $isWriteable;

        return new GetClassResult(classData: $classData);
    }
}
