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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Version\PublishVersion;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Exception\Asset\AssetVersionNotFoundException;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Version\PublishVersion\PublishVersionPayload;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\ElementServiceInterface;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Version;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class PublishVersionHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly ElementServiceInterface $elementService,
    ) {
    }

    public function __invoke(PublishVersionPayload $payload): PublishVersionResult
    {
        $versionId = $payload->versionId;
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        $version = Version::getById($versionId)
            ?? throw new AssetVersionNotFoundException($versionId);

        $asset = $version->loadData();
        if (!$asset instanceof Asset) {
            throw new AssetVersionNotFoundException($versionId);
        }

        $currentAsset = Asset::getById($asset->getId());
        if (!$currentAsset?->isAllowed('publish')) {
            throw new AccessDeniedHttpException();
        }

        $asset->setUserModification($userId);

        try {
            $asset->save();
        } catch (\Exception $e) {
            throw new AdminOperationFailedException($e->getMessage());
        }

        return new PublishVersionResult($this->elementService->getElementTreeNodeConfig($asset));
    }
}
