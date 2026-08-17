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

namespace OpenDxp\Bundle\AdminBundle\Handler\GDPR\DataObject\ExportDataObject;

use OpenDxp\Bundle\AdminBundle\GDPR\DataProvider\DataObjects;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdQueryPayload;
use OpenDxp\Model\DataObject;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ExportDataObjectHandler
{
    public function __construct(private readonly DataObjects $dataObjects)
    {
    }

    public function __invoke(IdQueryPayload $payload): ExportDataObjectResult
    {
        $object = DataObject::getById($payload->id);
        if (!$object) {
            throw new NotFoundHttpException('Object not found');
        }
        if (!$object->isAllowed('view')) {
            throw new AccessDeniedHttpException('Export denied');
        }

        return new ExportDataObjectResult($this->dataObjects->doExportData($object), $object->getId() ?? 0);
    }
}
