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

final class GetEmailLogsHandler
{
    public function __invoke(
        ?int $documentId,
        int $limit,
        int $offset,
        ?string $filter,
    ): GetEmailLogsResult {
        $list = new Tool\Email\Log\Listing();

        if ($documentId !== null) {
            $list->setCondition('documentId = ' . $documentId);
        }

        $list->setLimit($limit);
        $list->setOffset($offset);
        $list->setOrderKey('sentDate');
        $list->setOrder('DESC');

        if ($filter !== null) {
            if ($filter === '*') {
                $filter = '';
            }

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

            if ($documentId !== null) {
                $condition .= 'AND documentId = ' . $documentId;
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
