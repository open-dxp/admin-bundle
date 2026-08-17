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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\DocTypes\DeleteDocType;

use OpenDxp\Bundle\AdminBundle\Handler\Document\DocTypes\DocTypePayload;
use OpenDxp\Model\Document\DocType;
use OpenDxp\Model\Exception\ConfigWriteException;

final class DeleteDocTypeHandler
{
    public function __invoke(DocTypePayload $payload): DeleteDocTypeResult
    {
        $type = DocType::getById($payload->id);
        if (!$type->isWriteable()) {
            throw new ConfigWriteException();
        }
        $type->delete();

        return new DeleteDocTypeResult();
    }
}
