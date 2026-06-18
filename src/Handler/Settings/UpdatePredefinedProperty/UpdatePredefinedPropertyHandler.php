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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings\UpdatePredefinedProperty;

use OpenDxp\Bundle\AdminBundle\Handler\Settings\PredefinedPropertyPayload;
use OpenDxp\Model\Exception\ConfigWriteException;
use OpenDxp\Model\Property;

final class UpdatePredefinedPropertyHandler
{
    public function __invoke(PredefinedPropertyPayload $payload): UpdatePredefinedPropertyResult
    {
        $data = $payload->data;
        $property = Property\Predefined::getById($data['id']);

        if (!$property->isWriteable()) {
            throw new ConfigWriteException();
        }

        if (is_array($data['ctype'])) {
            $data['ctype'] = implode(',', $data['ctype']);
        }

        $property->setValues($data);
        $property->save();

        $responseData = $property->getObjectVars();
        $responseData['writeable'] = $property->isWriteable();

        return new UpdatePredefinedPropertyResult(data: $responseData);
    }
}
