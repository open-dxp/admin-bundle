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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\UpdateProperty;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Mapper\DataObject\ClassificationstoreKeyConfigMapper;
use OpenDxp\Model\DataObject\Classificationstore;

final class UpdatePropertyHandler
{
    public function __construct(private readonly ClassificationstoreKeyConfigMapper $keyConfigService,)
    {
    }

    public function __invoke(UpdatePropertyPayload $payload): UpdatePropertyResult
    {
        if (!$payload->hasData) {
            throw new AdminOperationFailedException();
        }

        $data = $payload->data;
        $id = $data['id'];
        $config = Classificationstore\KeyConfig::getById($id);

        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $setter = 'set' . $key;
                if (method_exists($config, $setter)) {
                    $config->$setter($value);
                }
            }
        }

        $config->save();
        $item = $this->keyConfigService->buildKeyConfigItem($config);

        return new UpdatePropertyResult(data: $item);
    }
}
