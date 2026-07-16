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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Copy\RewriteDataObjectIds;

use OpenDxp\Bundle\AdminBundle\Exception\DataObject\DataObjectNotFoundException;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Session\Gateway\CopySessionGateway;
use OpenDxp\Model\DataObject;

final class RewriteDataObjectIdsHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly CopySessionGateway $copySession,
    ) {
    }

    public function __invoke(RewriteDataObjectIdsPayload $payload): RewriteDataObjectIdsResult
    {
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        $objectId = $this->copySession->popRewriteStackId($payload->transactionId);
        $idMapping = $this->copySession->getIdMapping($payload->transactionId);
        $object = DataObject::getById($objectId) ?? throw new DataObjectNotFoundException($objectId);

        $object = DataObject\Service::rewriteIds($object, ['object' => $idMapping]);
        $object->setUserModification($userId);
        $object->save();

        return new RewriteDataObjectIdsResult($objectId);
    }
}
