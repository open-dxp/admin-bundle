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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef;

use OpenDxp\Model\DataObject;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ImportClassHandler
{
    public function __invoke(?string $id, string $json): void
    {
        $class = DataObject\ClassDefinition::getById($id);
        if (!$class) {
            throw new NotFoundHttpException('Class not found');
        }

        $success = DataObject\ClassDefinition\Service::importClassDefinitionFromJson($class, $json, false, true);
        if (!$success) {
            throw new RuntimeException('Failed to import class definition');
        }
    }
}
