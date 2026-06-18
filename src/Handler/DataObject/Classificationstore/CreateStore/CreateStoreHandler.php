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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\CreateStore;

use Exception;
use OpenDxp\Model\DataObject\Classificationstore;

final class CreateStoreHandler
{
    /**
     * @throws Exception
     */
    public function __invoke(CreateStorePayload $payload): CreateStoreResult
    {
        $config = Classificationstore\StoreConfig::getByName($payload->name);

        if (!$config) {
            $config = new Classificationstore\StoreConfig();
            $config->setName($payload->name);
            $config->save();
        } else {
            throw new Exception('Store with the given name exists');
        }

        return new CreateStoreResult(storeId: $config->getId());
    }
}
