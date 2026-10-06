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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\GetDownloadZipJobs;

use OpenDxp\Bundle\AdminBundle\Exception\Asset\AssetNotFoundException;
use OpenDxp\Bundle\AdminBundle\Service\Asset\AssetDownloadService;
use OpenDxp\Model\Asset;
use Symfony\Component\Routing\RouterInterface;

final class GetDownloadZipJobsHandler
{
    private const int FILES_PER_JOB = 5;

    public function __construct(
        private readonly AssetDownloadService $assetDownloadService,
        private readonly RouterInterface $router,
    ) {
    }

    public function __invoke(GetDownloadZipJobsPayload $payload): GetDownloadZipJobsResult
    {
        $asset = Asset::getById($payload->id) ?? throw new AssetNotFoundException($payload->id);
        $jobId = uniqid('', false);

        if (!$asset->isAllowed('view')) {
            return new GetDownloadZipJobsResult(jobId: $jobId, jobs: []);
        }

        if ($payload->thumbnail === null) {
            $assetList = $this->assetDownloadService->getZipFileListing($asset, $payload->selectedIds);
        } else {
            $this->assetDownloadService->getDownloadableThumbnailConfig($payload->thumbnail);
            $assetList = $this->assetDownloadService->getZipImageListing($asset, $payload->selectedIds);
        }

        $jobAmount = (int) ceil($assetList->getTotalCount() / self::FILES_PER_JOB);
        $addFilesUrl = $this->router->generate('opendxp_admin_asset_downloadaszipaddfiles');

        $jobs = [];
        for ($i = 0; $i < $jobAmount; $i++) {
            $jobs[] = [[
                'url' => $addFilesUrl,
                'method' => 'GET',
                'params' => array_filter([
                    'id' => $asset->getId(),
                    'selectedIds' => implode(',', $payload->selectedIds),
                    'offset' => $i * self::FILES_PER_JOB,
                    'limit' => self::FILES_PER_JOB,
                    'jobId' => $jobId,
                    'thumbnail' => $payload->thumbnail,
                ], static fn (mixed $value): bool => $value !== null),
            ]];
        }

        return new GetDownloadZipJobsResult(jobId: $jobId, jobs: $jobs);
    }
}
