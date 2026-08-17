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

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\Blocklist\DeleteBlocklistEntry;

use OpenDxp\Bundle\AdminBundle\Handler\Email\BlocklistPayload;
use OpenDxp\Model\Tool;

final class DeleteBlocklistEntryHandler
{
    public function __invoke(BlocklistPayload $payload): DeleteBlocklistEntryResult
    {
        $entry = Tool\Email\Blocklist::getByAddress($payload->data['address']);
        $entry->delete();

        return new DeleteBlocklistEntryResult();
    }
}
