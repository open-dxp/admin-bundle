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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject;

use OpenDxp\Model\DataObject;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use OpenDxp\Bundle\AdminBundle\Payload\DataObject\AddObjectFolderPayload;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class AddObjectFolderHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(AddObjectFolderPayload $payload): void
    {
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        $parentId = $payload->parentId;
        $key = $payload->key;
        $parent = DataObject::getById($parentId);
        if ($parent === null) {
            throw new NotFoundHttpException("Parent object not found: $parentId");
        }

        if (!$parent->isAllowed('create')) {
            throw new AccessDeniedHttpException('prevented creating folder because of missing permissions');
        }

        if (DataObject\Service::pathExists($parent->getRealFullPath() . '/' . $key)) {
            throw new BadRequestHttpException('folder with same path+key already exists');
        }

        $folder = DataObject\Folder::create([
            'parentId' => $parentId,
            'creationDate' => time(),
            'userOwner' => $userId,
            'userModification' => $userId,
            'key' => $key,
            'published' => true,
        ]);

        $folder->save();
    }
}
