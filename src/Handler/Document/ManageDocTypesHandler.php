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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document;

use OpenDxp\Model\Document\DocType;
use OpenDxp\Model\Exception\ConfigWriteException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class ManageDocTypesHandler
{
    public function __invoke(string $xaction, array $data): ManageDocTypesResult
    {
        if ($xaction === 'destroy') {
            $type = DocType::getById($data['id']);
            if (!$type->isWriteable()) {
                throw new ConfigWriteException();
            }
            $type->delete();

            return new ManageDocTypesResult(data: []);
        }

        if ($xaction === 'update') {
            $type = DocType::getById($data['id']);
            if (!$type->isWriteable()) {
                throw new ConfigWriteException();
            }
            $type->setValues($data);
            $type->save();
            $responseData = $type->getObjectVars();
            $responseData['writeable'] = $type->isWriteable();

            return new ManageDocTypesResult(data: $responseData);
        }

        if ($xaction === 'create') {
            if (!(new DocType())->isWriteable()) {
                throw new ConfigWriteException();
            }
            unset($data['id']);
            $type = DocType::create();
            $type->setValues($data);
            $type->save();
            $responseData = $type->getObjectVars();
            $responseData['writeable'] = $type->isWriteable();

            return new ManageDocTypesResult(data: $responseData);
        }

        throw new BadRequestHttpException('Unknown xaction: ' . $xaction);
    }
}
