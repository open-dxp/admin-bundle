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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\UpdateCollection;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Model\DataObject\Classificationstore;

final class UpdateCollectionHandler
{
    public function __invoke(UpdateCollectionPayload $payload): UpdateCollectionResult
    {
        if (!$payload->hasData) {
            throw new AdminOperationFailedException();
        }

        $data = $payload->data;
        $id = $data['id'];
        $config = Classificationstore\CollectionConfig::getById($id);

        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $setter = 'set' . $key;
                $config->$setter($value);
            }
        }

        $config->save();

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

        return new UpdateCollectionResult(data: $item);
    }
}
