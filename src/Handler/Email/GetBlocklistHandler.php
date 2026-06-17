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

namespace OpenDxp\Bundle\AdminBundle\Handler\Email;

use OpenDxp\Model\Tool;

final class GetBlocklistHandler
{
    public function __invoke(BlocklistPayload $payload): GetBlocklistResult
    {
        $list = new Tool\Email\Blocklist\Listing();

        $list->setLimit($payload->limit);
        $list->setOffset($payload->offset);

        if ($payload->sortingSettings['orderKey']) {
            $list->setOrderKey($payload->sortingSettings['orderKey']);
            $list->setOrder($payload->sortingSettings['order']);
        }

        if ($payload->filter !== null) {
            $list->setCondition('`address` LIKE ' . $list->quote('%' . $payload->filter . '%'));
        }

        $data = $list->load();
        $jsonData = [];
        foreach ($data as $entry) {
            $jsonData[] = $entry->getObjectVars();
        }

        return new GetBlocklistResult(data: $jsonData, total: $list->getTotalCount());
    }
}
