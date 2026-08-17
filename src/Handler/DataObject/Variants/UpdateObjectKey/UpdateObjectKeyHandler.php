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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Variants\UpdateObjectKey;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Service\DataObject\DataObjectGridService;
use OpenDxp\Model\DataObject;

final class UpdateObjectKeyHandler
{
    public function __construct(private readonly DataObjectGridService $dataObjectGridService,)
    {
    }

    public function __invoke(UpdateObjectKeyPayload $payload): UpdateObjectKeyResult
    {
        $id = $payload->id;
        $key = $payload->key;

        $object = DataObject\Concrete::getById($id);

        if (!$object) {
            throw new AdminOperationFailedException('No Object found for given id.');
        }

        return new UpdateObjectKeyResult(
            $this->dataObjectGridService->renameObject($object, $key),
        );
    }
}
