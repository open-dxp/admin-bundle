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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore;

use Exception;
use OpenDxp\Model\DataObject\Classificationstore;

final class EditStoreHandler
{
    /**
     * @throws Exception
     */
    public function __invoke(EditStorePayload $payload): void
    {
        $id = $payload->id;
        $name = $payload->name;
        if (!$name) {
            throw new Exception('Name must not be empty');
        }

        $config = Classificationstore\StoreConfig::getByName($name);
        if ($config && $config->getId() != $id) {
            throw new Exception('There is already a config with the same name');
        }

        $config = Classificationstore\StoreConfig::getById($id);

        if (!$config) {
            throw new Exception('Configuration does not exist');
        }

        $config->setName($name);
        $config->setDescription($payload->description);
        $config->save();
    }
}
