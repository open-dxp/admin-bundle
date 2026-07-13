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

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Service\GridData;
use OpenDxp\Bundle\AdminBundle\Session\Gateway\GridColumnConfigSessionGateway;
use OpenDxp\Model\DataObject;

final class LoadObjectDataHandler
{
    public function __construct(private readonly GridColumnConfigSessionGateway $gridColumnConfigSession) {}

    public function __invoke(LoadObjectDataPayload $payload): LoadObjectDataResult
    {
        $object = DataObject::getById($payload->id);
        if (!$object instanceof DataObject) {
            throw new AdminOperationFailedException(sprintf('DataObject with id %d not found', $payload->id));
        }

        return new LoadObjectDataResult(fields: GridData\DataObject::getData(
                $object,
                $payload->fields,
                params: [
                    'helperDefinitions' => $this->gridColumnConfigSession->getHelperColumns(),
                ]
            )
        );
    }
}
