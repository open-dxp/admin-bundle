<?php

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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\LoadObjectData;

use OpenDxp\Bundle\AdminBundle\Exception\DataObject\DataObjectNotFoundException;
use OpenDxp\Bundle\AdminBundle\Service\GridData;
use OpenDxp\Model\DataObject;

final class LoadObjectDataHandler
{
    public function __invoke(LoadObjectDataPayload $payload): array
    {
        $object = DataObject::getById($payload->id);
        if (!$object instanceof DataObject) {
            throw new DataObjectNotFoundException($payload->id);
        }

        return GridData\DataObject::getData($object, $payload->fields);
    }
}
