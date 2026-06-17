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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Copy\CopyDataObject;

use OpenDxp\Bundle\AdminBundle\Factory\ElementServiceFactory;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\DataObject;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CopyDataObjectHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly ElementServiceFactory $serviceFactory,
    ) {}

    public function __invoke(CopyDataObjectPayload $payload): CopyDataObjectResult
    {
        $adminUser = $this->userContext->getAdminUser();
        $source = DataObject::getById($payload->sourceId);

        if ($payload->sourceParentId !== null && $payload->targetParentId !== null) {
            $sourceParent = DataObject::getById($payload->sourceParentId) ?? throw new NotFoundHttpException('Source parent not found');
            $resolvedTargetParentId = $payload->sessionParentId ?? $payload->targetParentId;
            $targetParent = DataObject::getById($resolvedTargetParentId) ?? throw new NotFoundHttpException('Target parent not found');
            $targetPath = preg_replace('@^' . preg_quote($sourceParent->getRealFullPath(), '@') . '@', $targetParent . '/', $source->getRealPath());
            $target = DataObject::getByPath($targetPath);
        } else {
            $target = DataObject::getById($payload->targetId);
        }

        if (!$target instanceof DataObject) {
            throw new NotFoundHttpException('Target not found');
        }

        $hasClassPermission = !($source instanceof DataObject\Concrete) || $adminUser->isAllowed($source->getClassId(), 'class');
        if (!$target->isAllowed('create') || !$hasClassPermission) {
            throw new AccessDeniedHttpException();
        }

        $source = DataObject::getById($payload->sourceId);
        if (!$source instanceof DataObject) {
            throw new NotFoundHttpException("Source object not found: {$payload->sourceId}");
        }

        if ($source instanceof DataObject\Concrete && $latestVersion = $source->getLatestVersion()) {
            $source = $latestVersion->loadData();
            $source->setPublished(false);
        }

        $objectService = $this->serviceFactory->createDataObjectService();

        if ($payload->type === 'child') {
            $newObject = $objectService->copyAsChild($target, $source);

            return new CopyDataObjectResult($payload->sourceId, $newObject);
        }

        if ($payload->type === 'replace') {
            $concreteTarget = DataObject\Concrete::getById($target->getId());
            $concreteSource = DataObject\Concrete::getById($source->getId());
            $objectService->copyContents($concreteTarget, $concreteSource);
        }

        return new CopyDataObjectResult($payload->sourceId);
    }
}
