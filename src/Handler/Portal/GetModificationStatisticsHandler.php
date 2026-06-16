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

namespace OpenDxp\Bundle\AdminBundle\Handler\Portal;

use DateTime;

final class GetModificationStatisticsHandler
{
    public function __invoke(): GetModificationStatisticsResult
    {
        $db = \OpenDxp\Db::get();

        $days = 31;
        $startDate = mktime(23, 59, 59, (int) date('m'), (int) date('d'), (int) date('Y'));

        $data = [];

        for ($i = 0; $i < $days; $i++) {
            $end = $startDate - ($i * 86400);
            $start = $end - 86399;

            $o = $db->fetchOne(
                'SELECT COUNT(*) AS count FROM objects WHERE modificationDate > ? AND modificationDate < ?',
                [$start, $end]
            );
            $a = $db->fetchOne(
                'SELECT COUNT(*) AS count FROM assets WHERE modificationDate > ? AND modificationDate < ?',
                [$start, $end]
            );
            $d = $db->fetchOne(
                'SELECT COUNT(*) AS count FROM documents WHERE modificationDate > ? AND modificationDate < ?',
                [$start, $end]
            );

            $date = new DateTime();
            $date->setTimestamp($start);

            $data[] = [
                'timestamp' => $start,
                'datetext' => $date->format('Y-m-d'),
                'objects' => (int) $o,
                'documents' => (int) $d,
                'assets' => (int) $a,
            ];
        }

        return new GetModificationStatisticsResult(data: array_reverse($data));
    }
}
