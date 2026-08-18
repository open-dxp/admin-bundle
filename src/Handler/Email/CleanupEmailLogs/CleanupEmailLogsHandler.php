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

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\CleanupEmailLogs;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Service\Email\EmailLogCleaner;

final class CleanupEmailLogsHandler
{
    public function __construct(private readonly EmailLogCleaner $cleaner)
    {
    }

    public function __invoke(CleanupEmailLogsPayload $payload): CleanupEmailLogsResult
    {
        if ($payload->olderThanDays < CleanupEmailLogsPayload::MINIMUM_DAYS) {
            throw new AdminOperationFailedException(
                sprintf(
                    'Email logs can only be cleaned up from an age of %d day(s) on, got %d.',
                    CleanupEmailLogsPayload::MINIMUM_DAYS,
                    $payload->olderThanDays
                )
            );
        }

        return new CleanupEmailLogsResult(deleted: $this->cleaner->deleteOlderThanDays($payload->olderThanDays));
    }
}
