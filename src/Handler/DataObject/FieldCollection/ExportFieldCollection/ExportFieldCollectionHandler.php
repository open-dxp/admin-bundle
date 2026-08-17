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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\ExportFieldCollection;

use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\ExportFieldCollection\ExportFieldCollectionPayload;
use OpenDxp\Logger;
use OpenDxp\Model\DataObject;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ExportFieldCollectionHandler
{
    public function __invoke(ExportFieldCollectionPayload $payload): ExportFieldCollectionResult
    {
        $fieldCollection = DataObject\Fieldcollection\Definition::getByKey($payload->id);

        if (!$fieldCollection instanceof DataObject\Fieldcollection\Definition) {
            $errorMessage = ': Field-Collection with id [ ' . $payload->id . ' not found. ]';
            Logger::error($errorMessage);

            throw new NotFoundHttpException($errorMessage);
        }

        return new ExportFieldCollectionResult(
            $fieldCollection->getKey(),
            DataObject\ClassDefinition\Service::generateFieldCollectionJson($fieldCollection),
        );
    }
}
