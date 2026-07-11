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

namespace OpenDxp\Bundle\AdminBundle\Service\Grid;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Exception;
use OpenDxp\Bundle\AdminBundle\Dto\Grid\GridConfigData;
use OpenDxp\Bundle\AdminBundle\Model\GridConfig;
use OpenDxp\Bundle\AdminBundle\Model\GridConfigFavourite;
use OpenDxp\Bundle\AdminBundle\Model\GridConfigShare;
use OpenDxp\Db;
use OpenDxp\Model\User;
use OpenDxp\Security\SecurityHelper;

final class GridColumnConfigService
{
    public function getMyOwnColumnConfigs(int $userId, string $classId, ?string $searchType = null): array
    {
        $db = Db::get();
        $conditionParts = [
            'ownerId = ' . $userId,
            'classId = ' . $db->quote($classId),
        ];

        if ($searchType) {
            $conditionParts[] = 'searchType = ' . $db->quote($searchType);
        }

        $listing = new GridConfig\Listing();
        $listing->setOrderKey('name');
        $listing->setOrder('ASC');
        $listing->setCondition(implode(' AND ', $conditionParts));
        $listing = $listing->load();

        $data = [];
        foreach ($listing as $config) {
            $data[] = $config->getObjectVars();
        }

        return $data;
    }

    public function getSharedColumnConfigs(?User $user, string $classId, ?string $searchType = null): array
    {
        if (!$user) {
            return [];
        }

        $db = Db::get();

        $userIds = [$user->getId(), ...$user->getRoles()];

        $ids = $db->fetchFirstColumn(
            'SELECT DISTINCT c1.id FROM gridconfigs c1, gridconfig_shares s
                WHERE (c1.searchType = ? AND c1.id = s.gridConfigId AND s.sharedWithUserId IN (?) AND c1.classId = ?)
            UNION DISTINCT SELECT c2.id FROM gridconfigs c2 WHERE shareGlobally = 1 AND c2.classId = ? AND c2.ownerId != ?',
            [$searchType, $userIds, $classId, $classId, $user->getId()],
            [ParameterType::STRING, ArrayParameterType::INTEGER, ParameterType::STRING, ParameterType::STRING, ParameterType::INTEGER]
        );

        $data = [];
        if ($ids) {
            $listing = new GridConfig\Listing();
            $listing->setOrderKey('name');
            $listing->setOrder('ASC');
            $listing->setCondition('id in (' . implode(',', $ids) . ')');

            foreach ($listing->load() as $config) {
                $data[] = $config->getObjectVars();
            }
        }

        return $data;
    }

    public function getShareSettings(int $gridConfigId): array
    {
        $result = [
            'sharedUserIds' => [],
            'sharedRoleIds' => [],
        ];

        $db = Db::get();
        $allShares = $db->fetchAllAssociative(
            'SELECT s.sharedWithUserId, u.type FROM gridconfig_shares s, users u
                WHERE s.sharedWithUserId = u.id AND s.gridConfigId = ?',
            [$gridConfigId]
        );

        foreach ($allShares as $share) {
            $result['shared' . ucfirst($share['type']) . 'Ids'][] = $share['sharedWithUserId'];
        }

        foreach ($result as $idx => $value) {
            $result[$idx] = $value ? implode(',', $value) : '';
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    public function updateGridConfigShares(?GridConfig $gridConfig, array $metadata, ?User $user, bool $adminCanEditAll = false): void
    {
        if (!$gridConfig || !$user || !$user->isAllowed('share_configurations')) {
            return;
        }

        $ownerMismatch = $gridConfig->getOwnerId() !== $user->getId();
        if ($ownerMismatch && (!$adminCanEditAll || !$user->isAdmin())) {
            throw new Exception("don't mess with someone elses grid config");
        }

        $combinedShares = [];
        if ($metadata['sharedUserIds']) {
            $combinedShares = explode(',', $metadata['sharedUserIds']);
        }
        if ($metadata['sharedRoleIds']) {
            $combinedShares = [...$combinedShares, ...explode(',', $metadata['sharedRoleIds'])];
        }

        $db = Db::get();
        $db->delete('gridconfig_shares', ['gridConfigId' => $gridConfig->getId()]);

        foreach ($combinedShares as $id) {
            $share = new GridConfigShare();
            $share->setGridConfigId($gridConfig->getId());
            $share->setSharedWithUserId((int) $id);
            $share->save();
        }
    }

    /**
     * Loads a GridConfig by ID, verifies the user has access, and returns a populated DTO.
     * Returns an empty GridConfigData when no valid config ID is given or the config is not found.
     *
     * @throws Exception when the user has neither ownership nor a share grant
     */
    public function loadVerifiedGridConfig(
        int|string|null $requestedConfigId,
        ?User $user,
        ?string $expectedType = null,
    ): GridConfigData {
        if (!is_numeric($requestedConfigId) || (int) $requestedConfigId <= 0) {
            return new GridConfigData();
        }

        $savedGridConfig = GridConfig::getById((int) $requestedConfigId);
        if (!$savedGridConfig) {
            return new GridConfigData();
        }
        if ($expectedType !== null && $savedGridConfig->getType() !== $expectedType) {
            return new GridConfigData();
        }

        $isShared = false;
        if (!$user) {
            return new GridConfigData();
        }
        if (!$user->isAdmin()) {
            $userIds = [$user->getId(), ...$user->getRoles()];
            $isSharedGlobally = $savedGridConfig->getOwnerId() !== $user->getId() && $savedGridConfig->isShareGlobally();

            $db = Db::get();
            $isSharedWithUser = (bool) $db->fetchOne(
                'SELECT 1 FROM gridconfig_shares WHERE sharedWithUserId IN (?) AND gridConfigId = ?',
                [$userIds, $savedGridConfig->getId()],
                [ArrayParameterType::INTEGER, ParameterType::INTEGER]
            );

            $isShared = $isSharedGlobally || $isSharedWithUser;

            if (!$isShared && $savedGridConfig->getOwnerId() !== $user->getId()) {
                throw new Exception('You are neither the owner of this config nor it is shared with you');
            }
        }

        $config = json_decode($savedGridConfig->getConfig(), true);
        foreach ($config['columns'] as &$column) {
            if (array_key_exists('isOperator', $column) && $column['isOperator']) {
                $colAttributes = &$column['fieldConfig']['attributes'];
                SecurityHelper::convertHtmlSpecialCharsArrayKeys($colAttributes, ['label', 'attribute', 'param1']);
            }
        }

        return new GridConfigData(
            id: $savedGridConfig->getId() ?? 0,
            config: $config ?? [],
            name: SecurityHelper::convertHtmlSpecialChars($savedGridConfig->getName()),
            description: SecurityHelper::convertHtmlSpecialChars($savedGridConfig->getDescription()),
            sharedGlobally: $savedGridConfig->isShareGlobally(),
            setAsFavourite: $savedGridConfig->isSetAsFavourite(),
            isShared: $isShared,
            ownerId: $savedGridConfig->getOwnerId() ?? 0,
            modificationDate: $savedGridConfig->getModificationDate(),
            saveFilters: $savedGridConfig->isSaveFilters(),
        );
    }

    public function buildBaseSettings(GridConfigData $data): array
    {
        $settings = $this->getShareSettings($data->id);
        $settings['gridConfigId'] = $data->id;
        $settings['gridConfigName'] = $data->isEmpty() ? null : $data->name;
        $settings['gridConfigDescription'] = $data->isEmpty() ? null : $data->description;
        $settings['shareGlobally'] = $data->isEmpty() ? null : $data->sharedGlobally;
        $settings['setAsFavourite'] = $data->isEmpty() ? null : $data->setAsFavourite;
        $settings['isShared'] = $data->isEmpty() || $data->isShared;

        return $settings;
    }

    /**
     * @throws Exception
     */
    public function updateGridConfigFavourites(GridConfig $gridConfig, array $metadata, ?User $user, int $objectId = 0): void
    {
        if (!$user || !$user->isAllowed('share_configurations')) {
            return;
        }
        if (!$user->isAdmin() && $gridConfig->getOwnerId() !== $user->getId()) {
            throw new Exception("don't mess with someone elses grid config");
        }

        $sharedUsers = [];
        if ($metadata['shareGlobally'] === false && $metadata['sharedUserIds']) {
            $sharedUsers = array_map(intval(...), explode(',', $metadata['sharedUserIds']));
        } elseif ($metadata['shareGlobally'] === true) {
            $users = new User\Listing();
            $users->setCondition('id = ?', $user->getId());
            foreach ($users as $u) {
                $sharedUsers[] = $u->getId();
            }
        }

        foreach ($sharedUsers as $id) {
            if (!$this->canOverwriteFavourite($id, $gridConfig, $objectId)) {
                continue;
            }

            $favourite = new GridConfigFavourite();
            $favourite->setGridConfigId($gridConfig->getId());
            $favourite->setClassId($gridConfig->getClassId());
            $favourite->setObjectId($objectId);
            $favourite->setOwnerId($id);
            $favourite->setType($gridConfig->getType());
            $favourite->setSearchType($gridConfig->getSearchType());
            $favourite->save();

            if ($objectId !== 0 && $this->canOverwriteFavourite($id, $gridConfig, 0)) {
                $favourite->setObjectId(0);
                $favourite->save();
            }
        }
    }

    private function canOverwriteFavourite(int $userId, GridConfig $gridConfig, int $objectId): bool
    {
        $existing = GridConfigFavourite::getByOwnerAndClassAndObjectId(
            $userId,
            $gridConfig->getClassId(),
            $objectId,
            $gridConfig->getSearchType()
        );

        if (!($existing instanceof GridConfigFavourite)) {
            return true;
        }

        $existingConfig = GridConfig::getById($existing->getGridConfigId());
        if (!($existingConfig instanceof GridConfig)) {
            return true;
        }

        return $existingConfig->isShareGlobally() && $existingConfig->getOwnerId() !== $userId;
    }

    public function encode(null|string|array $value): string
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        }

        return '"' . str_replace('"', '""', $value ?? '') . '"';
    }
}
