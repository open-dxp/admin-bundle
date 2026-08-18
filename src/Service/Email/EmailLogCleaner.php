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

use DateTimeImmutable;
use InvalidArgumentException;
use OpenDxp\Model\Tool\Email;

final class EmailLogCleaner
{
    private const int PAGE_SIZE = 100;

    public function deleteOlderThanDays(int $days, ?callable $onProgress = null): int
    {
        if ($days < 0) {
            throw new InvalidArgumentException(sprintf('Days must not be negative, got %d.', $days));
        }

        $cutoff = (new DateTimeImmutable(sprintf('-%d days', $days)))->getTimestamp();
        $deleted = 0;

        do {
            $list = new Email\Log\Listing();
            $list->setCondition('sentDate < ?', [$cutoff]);

            $list->setOrderKey('id');
            $list->setOrder('ASC');
            $list->setLimit(self::PAGE_SIZE);

            $entries = $list->getEmailLogs();

            foreach ($entries as $entry) {
                $entry->delete();
                $deleted++;
            }

            if ($entries !== [] && $onProgress !== null) {
                $onProgress($deleted);
            }

        } while ($entries !== []);

        return $deleted;
    }
}
