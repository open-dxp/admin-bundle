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

use OpenDxp\Model\DataObject\Classificationstore;

final class AddPropertyHandler
{
    public function __invoke(string $name, int $storeId): AddPropertyResult
    {
        $definition = [
            'fieldtype' => 'input',
            'name' => $name,
            'title' => $name,
            'datatype' => 'data',
        ];

        $config = new Classificationstore\KeyConfig();
        $config->setName($name);
        $config->setTitle($name);
        $config->setType('input');
        $config->setStoreId($storeId);
        $config->setEnabled(true);
        $config->setDefinition(json_encode($definition));
        $config->save();

        return new AddPropertyResult(name: $config->getName());
    }
}
