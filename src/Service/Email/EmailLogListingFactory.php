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

namespace OpenDxp\Bundle\AdminBundle\Service\Email;

use OpenDxp\Model\Tool;

final class EmailLogListingFactory
{
    /**
     * @param int[] $ids
     */
    public function create(?int $documentId = null, ?string $filter = null, array $ids = []): Tool\Email\Log\Listing
    {
        $list = new Tool\Email\Log\Listing();

        $conditions = [];
        $variables = [];

        if ($documentId !== null) {
            $conditions[] = 'documentId = ?';
            $variables[] = $documentId;
        }

        if ($ids !== []) {
            $conditions[] = sprintf('id IN (%s)', implode(',', array_map('intval', $ids)));
        } elseif ($filter !== null) {
            $conditions[] = sprintf(
                'MATCH (`from`,`to`,`cc`,`bcc`,`subject`,`params`) AGAINST (%s IN BOOLEAN MODE)',
                $list->quote($this->normalizeFilter($filter))
            );
        }

        if ($conditions !== []) {
            $list->setCondition(implode(' AND ', $conditions), $variables);
        }

        $list->setOrderKey(['sentDate', 'id']);
        $list->setOrder(['DESC', 'DESC']);

        return $list;
    }

    private function normalizeFilter(string $filter): string
    {
        $filter = $filter === '*' ? '' : $filter;

        $filter = str_replace('%', '*', $filter);
        $filter = htmlspecialchars($filter, ENT_QUOTES);

        // A bare address would be split on "@", so keep addresses together as phrases.
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

        return $filter;
    }
}
