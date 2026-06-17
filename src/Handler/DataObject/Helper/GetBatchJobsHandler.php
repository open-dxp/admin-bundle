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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper;

use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridBatchService;

final class GetBatchJobsHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly GridBatchService $gridBatchService,
    ) {}

    public function __invoke(GetBatchJobsPayload $payload): GetBatchJobsResult
    {
        $adminUser = $this->userContext->getAdminUser();

        return new GetBatchJobsResult(
            jobs: $this->gridBatchService->getObjectBatchJobIds($payload->allParams, $payload->locale, $adminUser),
        );
    }
}
