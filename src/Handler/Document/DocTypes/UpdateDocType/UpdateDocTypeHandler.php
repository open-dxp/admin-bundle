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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\DocTypes\UpdateDocType;

use OpenDxp\Bundle\AdminBundle\Handler\Document\DocTypes\DocTypePayload;
use OpenDxp\Model\Document\DocType;
use OpenDxp\Model\Exception\ConfigWriteException;

final class UpdateDocTypeHandler
{
    public function __invoke(DocTypePayload $payload): UpdateDocTypeResult
    {
        $type = DocType::getById($payload->id);
        if (!$type->isWriteable()) {
            throw new ConfigWriteException();
        }
        $type->setValues($payload->data);
        $type->save();
        $responseData = $type->getObjectVars();
        $responseData['writeable'] = $type->isWriteable();

        return new UpdateDocTypeResult(data: $responseData);
    }
}
