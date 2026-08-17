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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings\GetWebsiteSettingsList;

use OpenDxp\Bundle\AdminBundle\Handler\Settings\WebsiteSettingPayload;
use OpenDxp\Model\WebsiteSetting;

final class GetWebsiteSettingsListHandler
{
    public function __invoke(WebsiteSettingPayload $payload): GetWebsiteSettingsListResult
    {
        $list = new WebsiteSetting\Listing();
        $list->setLimit($payload->limit);
        $list->setOffset($payload->offset);

        if ($payload->orderKey) {
            $list->setOrderKey($payload->orderKey);
            $list->setOrder($payload->order);
        } else {
            $list->setOrderKey('name');
            $list->setOrder('asc');
        }

        if ($payload->filter) {
            $list->setCondition('`name` LIKE ' . $list->quote('%' . $payload->filter . '%'));
        }

        $totalCount = $list->getTotalCount();
        $items = $list->load();

        $settings = [];
        foreach ($items as $item) {
            $settings[] = $this->buildEditModeData($item);
        }

        return new GetWebsiteSettingsListResult(data: $settings, total: $totalCount);
    }

    /**
     * @return array{id: ?int, name: string, language: string, type: string, data: mixed, siteId: ?int, creationDate: ?int, modificationDate: ?int}
     */
    private function buildEditModeData(WebsiteSetting $item): array
    {
        $resultItem = [
            'id' => $item->getId(),
            'name' => $item->getName(),
            'language' => $item->getLanguage(),
            'type' => $item->getType(),
            'data' => null,
            'siteId' => $item->getSiteId(),
            'creationDate' => $item->getCreationDate(),
            'modificationDate' => $item->getModificationDate(),
        ];

        switch ($item->getType()) {
            case 'document':
            case 'asset':
            case 'object':
                $element = $item->getData();
                if ($element) {
                    $resultItem['data'] = $element->getRealFullPath();
                }

                break;
            default:
                $resultItem['data'] = $item->getData();

                break;
        }

        return $resultItem;
    }
}
