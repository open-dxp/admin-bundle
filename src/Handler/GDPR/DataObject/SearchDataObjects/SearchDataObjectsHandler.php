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

namespace OpenDxp\Bundle\AdminBundle\Handler\GDPR\DataObject\SearchDataObjects;

use OpenDxp\Bundle\AdminBundle\GDPR\DataProvider\DataObjects;
use OpenDxp\Bundle\AdminBundle\Handler\GDPR\SearchDataPayload;

final class SearchDataObjectsHandler
{
    public function __construct(private readonly DataObjects $dataObjects)
    {
    }

    public function __invoke(SearchDataPayload $payload): SearchDataObjectsResult
    {
        $result = $this->dataObjects->searchData(
            $payload->id,
            $payload->firstname,
            $payload->lastname,
            $payload->email,
            $payload->start,
            $payload->limit,
            $payload->sort,
        );

        return new SearchDataObjectsResult($result);
    }
}
