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

namespace OpenDxp\Bundle\AdminBundle\Enricher\DataObject;

use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Version;

final class DraftEnricher
{
    public function enrich(Concrete $object, array &$data, ?Version $draftVersion): void
    {
        if (!$draftVersion instanceof Version) {
            return;
        }

        $fresh = Concrete::getById($object->getId(), ['force' => true]);
        if ($fresh->getModificationDate() < $draftVersion->getDate()) {
            $data['draft'] = [
                'id' => $draftVersion->getId(),
                'modificationDate' => $draftVersion->getDate(),
                'isAutoSave' => $draftVersion->isAutoSave(),
            ];
        }
    }
}
