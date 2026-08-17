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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings\UpdateWebsiteSetting;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\WebsiteSettingPayload;
use OpenDxp\Model\Element;
use OpenDxp\Model\WebsiteSetting;

final class UpdateWebsiteSettingHandler
{
    public function __invoke(WebsiteSettingPayload $payload): UpdateWebsiteSettingResult
    {
        $data = $payload->data;
        $setting = WebsiteSetting::getById($data['id']);

        if (!$setting instanceof WebsiteSetting) {
            throw new AdminOperationFailedException(sprintf('WebsiteSetting with id %d not found', $data['id']));
        }

        switch ($setting->getType()) {
            case 'document':
            case 'asset':
            case 'object':
                if (isset($data['data'])) {
                    $element = Element\Service::getElementByPath($setting->getType(), $data['data']);
                    $data['data'] = $element;
                }

                break;
        }

        $setting->setValues($data);
        $setting->save();

        return new UpdateWebsiteSettingResult(data: $this->buildEditModeData($setting));
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
