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

namespace OpenDxp\Bundle\AdminBundle\Service\Grid;

use OpenDxp\Bundle\AdminBundle\Dto\Grid\AssetGridColumnConfig;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Model\GridConfigFavourite;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Tool;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Metadata;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class AssetGridColumnConfigResolver
{
    public function __construct(
        private readonly GridColumnConfigService $gridColumnConfigService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AdminUserContextInterface $userContext,
    ) {}

    public function resolve(array $params, bool $isDelete = false): AssetGridColumnConfig
    {
        $user = $this->userContext->getAdminUser();
        $classId = $params['id'];
        $context = ['purpose' => 'gridconfig'];
        $types = !empty($params['types']) ? explode(',', $params['types']) : [];
        $userId = $user?->getId() ?? 0;
        $requestedGridConfigId = $isDelete ? null : ($params['gridConfigId'] ?? null);
        $searchType = $params['searchType'];

        if ((string) ($requestedGridConfigId ?? '') === '' && $classId) {
            $favourite = GridConfigFavourite::getByOwnerAndClassAndObjectId($userId, $classId, 0, $searchType);
            if ($favourite) {
                $requestedGridConfigId = $favourite->getGridConfigId();
            }
        }

        $configData = $this->gridColumnConfigService->loadVerifiedGridConfig($requestedGridConfigId, $user, 'asset');
        $gridConfig = $configData->config;

        $availableFields = [];
        if ($configData->isEmpty()) {
            $availableFields = $this->getDefaultGridFields($params['noSystemColumns'], $context, $types);
        } else {
            foreach ($gridConfig['columns'] as $sc) {
                if (!$sc['hidden']) {
                    $colConfig = $this->getFieldGridConfig($sc);
                    if ($colConfig) {
                        $availableFields[] = $colConfig;
                    }
                }
            }
        }
        usort($availableFields, static fn ($a, $b) => $a['position'] <=> $b['position']);

        $availableConfigs = $classId ? $this->gridColumnConfigService->getMyOwnColumnConfigs($userId, $classId, $searchType) : [];
        $sharedConfigs = $classId ? $this->gridColumnConfigService->getSharedColumnConfigs($user, $classId, $searchType) : [];
        $settings = $this->gridColumnConfigService->buildBaseSettings($configData);

        $gridContext = $gridConfig['context'] ?? null;
        if ($gridContext) {
            $gridContext = json_decode($gridContext, true);
        }

        return new AssetGridColumnConfig(
            availableFields: $availableFields,
            settings: $settings,
            availableConfigs: $availableConfigs,
            sharedConfigs: $sharedConfigs,
            sortinfo: $gridConfig['sortinfo'] ?? false,
            onlyDirectChildren: $gridConfig['onlyDirectChildren'] ?? false,
            pageSize: $gridConfig['pageSize'] ?? false,
            context: $gridContext,
            onlyUnreferenced: $gridConfig['onlyUnreferenced'] ?? false,
        );
    }

    private function getDefaultGridFields(bool $noSystemColumns, array $context, array $types = []): array
    {
        $count = 0;
        $availableFields = [];

        if (!$noSystemColumns) {
            foreach (Asset\Service::GRID_SYSTEM_COLUMNS as $sc) {
                if ($types === []) {
                    $availableFields[] = [
                        'key' => $sc . '~system',
                        'type' => 'system',
                        'label' => $sc,
                        'position' => $count,
                    ];
                    $count++;
                }
            }
        }

        return $availableFields;
    }

    private function getFieldGridConfig(array $field, ?string $keyPrefix = null): ?array
    {
        $defaultMetadataFields = ['copyright', 'alt', 'title'];
        $predefined = null;

        if (isset($field['fieldConfig']['layout']['name'])) {
            $predefined = Metadata\Predefined::getByName($field['fieldConfig']['layout']['name']);
        }

        $key = $field['name'];
        if ($keyPrefix) {
            $key = $keyPrefix . $key;
        }
        $fieldDef = explode('~', $field['name']);
        $field['name'] = $fieldDef[0];

        if (isset($fieldDef[1]) && $fieldDef[1] === 'system') {
            $type = 'system';
        } elseif (in_array($fieldDef[0], $defaultMetadataFields)) {
            $type = 'input';
        } else {
            $type = $field['fieldConfig']['type'];
            if (isset($fieldDef[1])) {
                $field['fieldConfig']['label'] = $field['fieldConfig']['layout']['title'] = $fieldDef[0] . ' (' . $fieldDef[1] . ')';
                $field['fieldConfig']['layout']['icon'] = Tool::getLanguageFlagFile($fieldDef[1], true);
            }
        }

        $result = [
            'key' => $key,
            'type' => $type,
            'label' => $field['fieldConfig']['label'] ?? $key,
            'width' => $field['width'],
            'position' => $field['position'],
            'language' => $field['fieldConfig']['language'] ?? null,
            'layout' => $field['fieldConfig']['layout'] ?? null,
        ];

        if (isset($field['locked'])) {
            $result['locked'] = $field['locked'];
        }

        if ($type === 'select' && $predefined) {
            $field['fieldConfig']['layout']['config'] = $predefined->getConfig();
            $result['layout'] = $field['fieldConfig']['layout'];
        } elseif (in_array($type, ['document', 'asset', 'object'], true)) {
            $result['layout']['fieldtype'] = 'manyToOneRelation';
            $result['layout']['subtype'] = $type;
        }

        $event = new GenericEvent(null, [
            'field' => $field,
            'result' => $result,
        ]);
        $this->eventDispatcher->dispatch($event, AdminEvents::ASSET_GET_FIELD_GRID_CONFIG);

        return $event->getArgument('result');
    }
}
