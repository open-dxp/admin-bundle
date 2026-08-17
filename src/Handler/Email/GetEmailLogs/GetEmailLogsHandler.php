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

use OpenDxp\Model\Tool;

final class GetEmailLogsHandler
{
    public function __invoke(GetEmailLogsPayload $payload): GetEmailLogsResult
    {
        $list = new Tool\Email\Log\Listing();

        if ($payload->documentId !== null) {
            $list->setCondition('documentId = ' . $payload->documentId);
        }

        $list->setLimit($payload->limit);
        $list->setOffset($payload->start);
        $list->setOrderKey('sentDate');
        $list->setOrder('DESC');

        if ($payload->filter !== null) {
            $filter = $payload->filter === '*' ? '' : $payload->filter;

            $filter = str_replace('%', '*', $filter);
            $filter = htmlspecialchars($filter, ENT_QUOTES);

            if (strpos($filter, '@')) {
                $parts = explode(' ', $filter);
                $parts = array_map(static function ($part) {
                    if (strpos($part, '@')) {
                        return '"' . $part . '"';
                    }

                    return $part;
                }, $parts);
                $filter = implode(' ', $parts);
            }

            if (str_starts_with($filter, '@')) {
                $filter = str_replace('@', '', $filter);
            }

            $condition = '( MATCH (`from`,`to`,`cc`,`bcc`,`subject`,`params`) AGAINST (' . $list->quote($filter) . ' IN BOOLEAN MODE) )';

            if ($payload->documentId !== null) {
                $condition .= 'AND documentId = ' . $payload->documentId;
            }

            $list->setCondition($condition);
        }

        $data = $list->load();
        $jsonData = [];

        foreach ($data as $entry) {
            $tmp = $entry->getObjectVars();
            unset($tmp['bodyHtml'], $tmp['bodyText']);
            $jsonData[] = $tmp;
        }

        return new GetEmailLogsResult(data: $jsonData, total: $list->getTotalCount());
    }
}
