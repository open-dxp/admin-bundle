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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\CreateCollection;

use OpenDxp\Model\DataObject\Classificationstore;

final class CreateCollectionHandler
{
    public function __invoke(CreateCollectionPayload $payload): CreateCollectionResult
    {
        $config = Classificationstore\CollectionConfig::getByName($payload->name, $payload->storeId);

        if (!$config) {
            $config = new Classificationstore\CollectionConfig();
            $config->setName($payload->name);
            $config->setStoreId($payload->storeId);
            $config->save();
        }

        return new CreateCollectionResult(id: $config->getName());
    }
}
