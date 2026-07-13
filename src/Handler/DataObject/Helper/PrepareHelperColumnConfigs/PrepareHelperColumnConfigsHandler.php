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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\PrepareHelperColumnConfigs;

use OpenDxp\Bundle\AdminBundle\Session\Gateway\GridColumnConfigSessionGateway;

final class PrepareHelperColumnConfigsHandler
{
    public function __construct(private readonly GridColumnConfigSessionGateway $gridColumnConfigSession) {}

    public function __invoke(PrepareHelperColumnConfigsPayload $payload): PrepareHelperColumnConfigsResult
    {
        $helperColumns = [];
        $newData = [];

        foreach ($payload->columns as $item) {
            if (!empty($item->isOperator)) {
                $itemKey = '#' . uniqid('', false);
                $item->key = $itemKey;
                $helperColumns[$itemKey] = $item;
            }
            $newData[] = $item;
        }

        $this->gridColumnConfigSession->prependHelperColumns($helperColumns);

        return new PrepareHelperColumnConfigsResult(
            columns: $newData,
            helperColumns: $helperColumns,
        );
    }
}
