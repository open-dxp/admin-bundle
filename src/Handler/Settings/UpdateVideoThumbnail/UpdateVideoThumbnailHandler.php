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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings\UpdateVideoThumbnail;

use OpenDxp\Model\Asset;
use OpenDxp\Model\Exception\ConfigWriteException;

final class UpdateVideoThumbnailHandler
{
    public function __invoke(UpdateVideoThumbnailPayload $payload): void
    {
        $pipe = Asset\Video\Thumbnail\Config::getByName($payload->name);

        if (!$pipe->isWriteable()) {
            throw new ConfigWriteException();
        }

        foreach ($payload->settingsData as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($pipe, $setter)) {
                $pipe->$setter($value);
            }
        }

        $pipe->resetItems();

        $mediaData = $payload->mediaData;
        $mediaOrder = $payload->mediaOrder;

        uksort($mediaData, static function ($a, $b) use ($mediaOrder) {
            if ($a === 'default') {
                return -1;
            }

            return ($mediaOrder[$a] < $mediaOrder[$b]) ? -1 : 1;
        });

        foreach ($mediaData as $mediaName => $items) {
            foreach ($items as $item) {
                $type = $item['type'];
                unset($item['type']);

                $pipe->addItem($type, $item, htmlspecialchars($mediaName));
            }
        }

        $pipe->save();
    }
}
