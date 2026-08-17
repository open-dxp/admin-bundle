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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\GetExportJobs;

use OpenDxp\Bundle\AdminBundle\Helper\GridHelperService;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridExportService;
use OpenDxp\Tool\Storage;

final class GetExportJobsHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly GridHelperService $gridHelperService,
        private readonly GridExportService $gridExportService,
    ) {
    }

    public function __invoke(GetExportJobsPayload $payload): GetExportJobsResult
    {
        $allParams = $payload->allParams;
        $adminUser = $this->userContext->getAdminUser();
        $list = $this->gridHelperService->prepareAssetListingForGrid($allParams, $adminUser);

        if (empty($ids = $allParams['ids'] ?? '')) {
            $ids = $list->loadIdList();
        }

        $jobs = array_chunk($ids, 20);

        $fileHandle = uniqid('asset-export-', false);
        $storage = Storage::get('temp');
        $storage->write($this->gridExportService->getCsvFile($fileHandle), '');

        return new GetExportJobsResult($jobs, $fileHandle);
    }
}
