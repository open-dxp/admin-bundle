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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper;

use OpenDxp\Bundle\AdminBundle\Model\GridConfig;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridColumnConfigService;
use OpenDxp\Model\Asset;
use OpenDxp\Model\User;
use OpenDxp\Version;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class SaveGridColumnConfigHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly GridColumnConfigService $gridColumnConfigService,
    ) {}

    public function __invoke(
        int $assetId,
        ?string $classId,
        ?string $context,
        ?string $searchType,
        ?string $type,
        array $gridConfigData,
        ?array $metadata,
    ): SaveGridColumnConfigResult {
        $adminUser = $this->userContext->getAdminUser();
        $asset = Asset::getById($assetId);
        if (!$asset) {
            throw new NotFoundHttpException();
        }

        if (!$asset->isAllowed('list')) {
            throw new AccessDeniedHttpException();
        }

        $gridConfigData['opendxp_version'] = Version::getVersion();
        $gridConfigData['opendxp_revision'] = Version::getRevision();
        $gridConfigData['context'] = $context;
        unset($gridConfigData['settings']['isShared']);

        $gridConfigId = $metadata['gridConfigId'] ?? null;
        $gridConfig = null;
        if ($gridConfigId) {
            $gridConfig = GridConfig::getById($gridConfigId);
        }

        if ($gridConfig && $gridConfig->getOwnerId() !== $adminUser->getId()) {
            throw new BadRequestHttpException("don't mess around with somebody else's configuration");
        }

        $this->gridColumnConfigService->updateGridConfigShares($gridConfig, $metadata ?? [], $adminUser);

        if (!$gridConfig) {
            $gridConfig = new GridConfig();
            $gridConfig->setName(date('c'));
            $gridConfig->setClassId($classId);
            $gridConfig->setSearchType($searchType);
            $gridConfig->setType($type);
            $gridConfig->setOwnerId($adminUser->getId());
        }

        if ($metadata) {
            $gridConfig->setName($metadata['gridConfigName']);
            $gridConfig->setDescription($metadata['gridConfigDescription']);
            $gridConfig->setShareGlobally($metadata['shareGlobally'] && $adminUser->isAdmin());
            $gridConfig->setSetAsFavourite($metadata['setAsFavourite'] && $adminUser->isAdmin());
        }

        $gridConfig->setConfig(json_encode($gridConfigData));
        $gridConfig->save();

        if (!empty($metadata['setAsFavourite']) && $adminUser->isAdmin()) {
            $this->gridColumnConfigService->updateGridConfigFavourites($gridConfig, $metadata, $adminUser);
        }

        $availableConfigs = $this->gridColumnConfigService->getMyOwnColumnConfigs($adminUser->getId(), $classId ?? '', $searchType);
        $sharedConfigs = $this->gridColumnConfigService->getSharedColumnConfigs($adminUser, $classId ?? '', $searchType);

        $settings = $this->gridColumnConfigService->getShareSettings($gridConfig->getId());
        $settings['gridConfigId'] = (int) $gridConfig->getId();
        $settings['gridConfigName'] = $gridConfig->getName();
        $settings['gridConfigDescription'] = $gridConfig->getDescription();
        $settings['shareGlobally'] = $gridConfig->isShareGlobally();
        $settings['setAsFavourite'] = $gridConfig->isSetAsFavourite();
        $settings['isShared'] = $gridConfig->getOwnerId() !== $adminUser->getId();

        return new SaveGridColumnConfigResult($settings, $availableConfigs, $sharedConfigs);
    }
}
