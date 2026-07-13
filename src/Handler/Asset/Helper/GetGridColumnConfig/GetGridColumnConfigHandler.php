<?php

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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\GetGridColumnConfig;

use OpenDxp\Bundle\AdminBundle\Service\Grid\AssetGridColumnConfigResolver;

final class GetGridColumnConfigHandler
{
    public function __construct(private readonly AssetGridColumnConfigResolver $gridConfigResolver) {}

    public function __invoke(GetGridColumnConfigPayload $payload): GetGridColumnConfigResult
    {
        $params = [
            'id'              => $payload->id,
            'types'           => $payload->types,
            'gridConfigId'    => $payload->gridConfigId,
            'searchType'      => $payload->searchType,
            'noSystemColumns' => $payload->noSystemColumns,
        ];

        $config = $this->gridConfigResolver->resolve($params);

        return new GetGridColumnConfigResult($config->toArray());
    }
}
