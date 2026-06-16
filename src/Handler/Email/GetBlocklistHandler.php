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

use OpenDxp\Bundle\AdminBundle\Helper\QueryParams;
use OpenDxp\Model\Tool;

final class GetBlocklistHandler
{
    public function __invoke(
        int $limit,
        int $offset,
        array $sortingSettings,
        ?string $filter,
    ): GetBlocklistResult {
        $list = new Tool\Email\Blocklist\Listing();

        $list->setLimit($limit);
        $list->setOffset($offset);

        if ($sortingSettings['orderKey']) {
            $list->setOrderKey($sortingSettings['orderKey']);
            $list->setOrder($sortingSettings['order']);
        }

        if ($filter !== null) {
            $list->setCondition('`address` LIKE ' . $list->quote('%' . $filter . '%'));
        }

        $data = $list->load();
        $jsonData = [];
        foreach ($data as $entry) {
            $jsonData[] = $entry->getObjectVars();
        }

        return new GetBlocklistResult(data: $jsonData, total: $list->getTotalCount());
    }
}
