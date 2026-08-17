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

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\DeleteAllVersions;

use OpenDxp\Model;

final class DeleteAllVersionsHandler
{
    public function __invoke(DeleteAllVersionsPayload $payload): void
    {
        $versions = new Model\Version\Listing();
        $versions->setCondition(
            'cid = ' . $versions->quote($payload->id) .
            ' AND date <> ' . $versions->quote($payload->date) .
            ' AND ctype = ' . $versions->quote($payload->type)
        );
        foreach ($versions->load() as $version) {
            $version->delete();
        }
    }
}
