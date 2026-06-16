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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings;

use OpenDxp\Model\Asset;

final class GetThumbnailHandler
{
    public function __invoke(string $name): GetThumbnailResult
    {
        $pipe = Asset\Image\Thumbnail\Config::getByName($name);
        $data = $pipe->getObjectVars();
        $data['writeable'] = $pipe->isWriteable();

        return new GetThumbnailResult(data: $data);
    }
}
