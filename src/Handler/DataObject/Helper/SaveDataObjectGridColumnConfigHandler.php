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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper;

use OpenDxp\Bundle\AdminBundle\Model\GridConfig;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridColumnConfigService;
use OpenDxp\Model\DataObject;
use OpenDxp\Security\SecurityHelper;
use OpenDxp\Version;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class SaveDataObjectGridColumnConfigHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly GridColumnConfigService $gridColumnConfigService,
    ) {}

    public function __invoke(SaveDataObjectGridColumnConfigPayload $payload): SaveDataObjectGridColumnConfigResult
    {
        $adminUser = $this->userContext->getAdminUser();
        $object = DataObject::getById($payload->objectId);
        if (!$object) {
            throw new NotFoundHttpException();
        }

        if (!$object->isAllowed('list')) {
            throw new AccessDeniedHttpException();
        }

        $gridConfigData = $payload->gridConfigData;
        $metadata = $payload->metadata;

        $gridConfigData['opendxp_version'] = Version::getVersion();
        $gridConfigData['opendxp_revision'] = Version::getRevision();
        $gridConfigData['context'] = $payload->context;
        unset($gridConfigData['settings']['isShared']);

        $gridConfigId = $metadata['gridConfigId'] ?? null;
        $gridConfig = null;
        if ($gridConfigId) {
            $gridConfig = GridConfig::getById($gridConfigId);
        }

        if ($gridConfig && $gridConfig->getOwnerId() !== $adminUser->getId() && !$adminUser->isAdmin()) {
            throw new BadRequestHttpException("don't mess around with somebody elses configuration");
        }

        $this->gridColumnConfigService->updateGridConfigShares($gridConfig, $metadata ?? [], $adminUser, adminCanEditAll: true);

        if (!empty($metadata['setAsFavourite']) && $adminUser->isAdmin()) {
            $this->gridColumnConfigService->updateGridConfigFavourites($gridConfig, $metadata, $adminUser, $payload->objectId);
        }

        if (!$gridConfig) {
            $gridConfig = new GridConfig();
            $gridConfig->setName(date('c'));
            $gridConfig->setClassId($payload->classId);
            $gridConfig->setSearchType($payload->searchType);
            $gridConfig->setOwnerId($adminUser->getId());
        }

        if ($metadata) {
            $gridConfig->setName(SecurityHelper::convertHtmlSpecialChars($metadata['gridConfigName']));
            $gridConfig->setDescription(SecurityHelper::convertHtmlSpecialChars($metadata['gridConfigDescription']));
            $gridConfig->setShareGlobally($metadata['shareGlobally'] && $adminUser->isAdmin());
            $gridConfig->setSetAsFavourite($metadata['setAsFavourite'] && $adminUser->isAdmin());
            $gridConfig->setSaveFilters($metadata['saveFilters'] ?? false);
        }

        $gridConfig->setConfig(json_encode($gridConfigData));
        $gridConfig->save();

        $availableConfigs = $this->gridColumnConfigService->getMyOwnColumnConfigs($adminUser->getId(), $payload->classId ?? '', $payload->searchType);
        $sharedConfigs = $this->gridColumnConfigService->getSharedColumnConfigs($adminUser, $payload->classId ?? '', $payload->searchType);

        $settings = $this->gridColumnConfigService->getShareSettings($gridConfig->getId());
        $settings['gridConfigId'] = (int) $gridConfig->getId();
        $settings['gridConfigName'] = SecurityHelper::convertHtmlSpecialChars($gridConfig->getName());
        $settings['gridConfigDescription'] = SecurityHelper::convertHtmlSpecialChars($gridConfig->getDescription());
        $settings['shareGlobally'] = $gridConfig->isShareGlobally();
        $settings['setAsFavourite'] = $gridConfig->isSetAsFavourite();
        $settings['saveFilters'] = $gridConfig->isSaveFilters();
        $settings['isShared'] = $gridConfig->getOwnerId() !== $adminUser->getId() && !$adminUser->isAdmin();

        return new SaveDataObjectGridColumnConfigResult($settings, $availableConfigs, $sharedConfigs);
    }
}
