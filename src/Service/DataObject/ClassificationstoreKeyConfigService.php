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

namespace OpenDxp\Bundle\AdminBundle\Service\DataObject;

use OpenDxp\Model\DataObject\Classificationstore;

final class ClassificationstoreKeyConfigService
{
    public function buildKeyConfigItem(Classificationstore\KeyConfig $config): array
    {
        $name = $config->getName();

        $item = [
            'storeId' => $config->getStoreId(),
            'id' => $config->getId(),
            'name' => $name,
            'description' => $config->getDescription(),
        ];

        if ($config->getCreationDate()) {
            $item['creationDate'] = $config->getCreationDate();
        }

        if ($config->getModificationDate()) {
            $item['modificationDate'] = $config->getModificationDate();
        }

        $item['type'] = $config->getType() ?: 'input';
        $definition = $config->getDefinition();
        $item['definition'] = $definition;

        if ($definition) {
            $definition = json_decode($definition, true);
            if ($definition) {
                $item['title'] = $definition['title'];
            }
        }

        return $item;
    }
}
