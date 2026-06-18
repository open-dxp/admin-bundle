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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Copy\CopyAsset;

use OpenDxp\Bundle\AdminBundle\Factory\ElementServiceFactory;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Copy\CopyAsset\CopyAssetPayload;
use OpenDxp\Logger;
use OpenDxp\Model\Asset;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CopyAssetHandler
{
    public function __construct(private readonly ElementServiceFactory $serviceFactory) {}

    public function __invoke(CopyAssetPayload $payload): CopyAssetResult
    {
        $sourceId = $payload->sourceId;
        $targetId = $payload->targetId;
        $type = $payload->type;
        $sourceParentId = $payload->sourceParentId;
        $targetParentId = $payload->targetParentId;
        $sessionParentId = $payload->sessionParentId;
        $source = Asset::getById($sourceId);

        if ($source === null) {
            throw new NotFoundHttpException('Source asset not found');
        }

        if ($sourceParentId !== null && $targetParentId !== null) {
            $sourceParent = Asset::getById($sourceParentId) ?? throw new NotFoundHttpException('Source parent not found');
            $resolvedTargetParentId = $sessionParentId ?? $targetParentId;
            $targetParent = Asset::getById($resolvedTargetParentId) ?? throw new NotFoundHttpException('Target parent not found');
            $targetPath = preg_replace('@^' . $sourceParent->getRealFullPath() . '@', $targetParent . '/', $source->getRealPath());
            $target = Asset::getByPath($targetPath);
        } else {
            $target = Asset::getById($targetId);
        }

        if ($target === null) {
            throw new NotFoundHttpException('Target not found');
        }

        if (!$target->isAllowed('create')) {
            Logger::error('could not execute copy/paste because of missing permissions on target [ ' . $targetId . ' ]');
            throw new AccessDeniedHttpException();
        }

        $assetService = $this->serviceFactory->createAssetService();

        if ($type === 'child') {
            $newAsset = $assetService->copyAsChild($target, $source);

            return new CopyAssetResult($newAsset);
        }

        if ($type === 'replace') {
            $assetService->copyContents($target, $source);
        }

        return new CopyAssetResult();
    }
}
