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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings\AddThumbnail;

use OpenDxp\Model\Asset;
use OpenDxp\Model\Exception\ConfigWriteException;

final class AddThumbnailHandler
{
    public function __invoke(AddThumbnailPayload $payload): AddThumbnailResult
    {
        $pipe = Asset\Image\Thumbnail\Config::getByName($payload->name);

        if (!$pipe) {
            $pipe = new Asset\Image\Thumbnail\Config();
            if (!$pipe->isWriteable()) {
                throw new ConfigWriteException();
            }
            $pipe->setName($payload->name);
            $pipe->save();

            return new AddThumbnailResult(id: $pipe->getName(), created: true);
        }

        if (!$pipe->isWriteable()) {
            throw new ConfigWriteException();
        }

        return new AddThumbnailResult(id: $pipe->getName(), created: false);
    }
}
