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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\PrepareHelperColumnConfigs;

use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\PrepareHelperColumnConfigs\PrepareHelperColumnConfigsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\PrepareHelperColumnConfigs\PrepareHelperColumnConfigsResult;

final class PrepareHelperColumnConfigsHandler
{
    public function __invoke(PrepareHelperColumnConfigsPayload $payload): PrepareHelperColumnConfigsResult
    {
        $helperColumns = [];
        $newData = [];

        foreach ($payload->columns as $item) {
            if (!empty($item->isOperator)) {
                $itemKey = '#' . uniqid('', false);

                $item->key = $itemKey;
                $newData[] = $item;
                $helperColumns[$itemKey] = $item;
            } else {
                $newData[] = $item;
            }
        }

        return new PrepareHelperColumnConfigsResult(
            columns:       $newData,
            helperColumns: $helperColumns,
        );
    }
}
