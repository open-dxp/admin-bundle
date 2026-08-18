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

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\GetEmailLogs;

use OpenDxp\Bundle\AdminBundle\Service\Email\EmailLogListingFactory;

final class GetEmailLogsHandler
{
    public function __construct(private readonly EmailLogListingFactory $listingFactory)
    {
    }

    public function __invoke(GetEmailLogsPayload $payload): GetEmailLogsResult
    {
        $list = $this->listingFactory->create($payload->documentId, $payload->filter);

        $list->setLimit($payload->limit);
        $list->setOffset($payload->start);

        $jsonData = [];

        foreach ($list->getEmailLogs() as $entry) {
            $tmp = $entry->getObjectVars();
            unset($tmp['bodyHtml'], $tmp['bodyText']);
            $jsonData[] = $tmp;
        }

        return new GetEmailLogsResult(data: $jsonData, total: $list->getTotalCount());
    }
}
