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

namespace OpenDxp\Bundle\AdminBundle\Handler\GDPR\Asset\SearchAssets;

use OpenDxp\Bundle\AdminBundle\GDPR\DataProvider\Assets;
use OpenDxp\Bundle\AdminBundle\Handler\GDPR\SearchDataPayload;

final class SearchAssetsHandler
{
    public function __construct(
        private readonly Assets $assets,
    ) {}

    public function __invoke(SearchDataPayload $payload): SearchAssetsResult
    {
        $result = $this->assets->searchData(
            $payload->id,
            $payload->firstname,
            $payload->lastname,
            $payload->email,
            $payload->start,
            $payload->limit,
            $payload->sort,
        );

        return new SearchAssetsResult($result);
    }
}
