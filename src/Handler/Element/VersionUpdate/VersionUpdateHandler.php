<?php

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

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\VersionUpdate;

use OpenDxp\Model\Version;

final class VersionUpdateHandler
{
    public function __invoke(VersionUpdatePayload $payload): void
    {
        $data = $payload->data ?? [];
        $version = Version::getById($data['id']);

        if ($version && ($data['public'] != $version->getPublic() || $data['note'] != $version->getNote())) {
            $version->setPublic($data['public']);
            $version->setNote($data['note']);
            $version->save();
        }
    }
}
