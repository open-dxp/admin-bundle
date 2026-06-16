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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Copy;

use OpenDxp\Bundle\AdminBundle\Factory\ElementServiceFactory;
use OpenDxp\Logger;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class CopyDataObjectHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,private readonly ElementServiceFactory $serviceFactory) {}

    public function __invoke(
        int $sourceId,
        int $targetId,
        string $type,
        ?int $sourceParentId,
        ?int $targetParentId,
        ?int $sessionParentId,
    ): CopyDataObjectResult {
        $adminUser = $this->userContext->getAdminUser();
        $source = DataObject::getById($sourceId);

        if ($sourceParentId !== null && $targetParentId !== null) {
            $sourceParent = DataObject::getById($sourceParentId) ?? throw new NotFoundHttpException('Source parent not found');
            $resolvedTargetParentId = $sessionParentId ?? $targetParentId;
            $targetParent = DataObject::getById($resolvedTargetParentId) ?? throw new NotFoundHttpException('Target parent not found');
            $targetPath = preg_replace('@^' . preg_quote($sourceParent->getRealFullPath(), '@') . '@', $targetParent . '/', $source->getRealPath());
            $target = DataObject::getByPath($targetPath);
        } else {
            $target = DataObject::getById($targetId);
        }

        if (!$target instanceof DataObject) {
            throw new NotFoundHttpException('Target not found');
        }

        $hasClassPermission = !($source instanceof DataObject\Concrete) || $adminUser->isAllowed($source->getClassId(), 'class');
        if (!$target->isAllowed('create') || !$hasClassPermission) {
            throw new AccessDeniedHttpException();
        }

        $source = DataObject::getById($sourceId);
        if (!$source instanceof DataObject) {
            throw new NotFoundHttpException("Source object not found: $sourceId");
        }

        if ($source instanceof DataObject\Concrete && $latestVersion = $source->getLatestVersion()) {
            $source = $latestVersion->loadData();
            $source->setPublished(false);
        }

        $objectService = $this->serviceFactory->createDataObjectService();

        if ($type === 'child') {
            $newObject = $objectService->copyAsChild($target, $source);

            return new CopyDataObjectResult($sourceId, $newObject);
        }

        if ($type === 'replace') {
            $concreteTarget = DataObject\Concrete::getById($target->getId());
            $concreteSource = DataObject\Concrete::getById($source->getId());
            $objectService->copyContents($concreteTarget, $concreteSource);
        }

        return new CopyDataObjectResult($sourceId);
    }
}
