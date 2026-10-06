<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\CountZipImages;

use OpenDxp\Bundle\AdminBundle\Exception\Asset\AssetNotFoundException;
use OpenDxp\Bundle\AdminBundle\Service\Asset\AssetDownloadService;
use OpenDxp\Model\Asset;

final readonly class CountZipImagesHandler
{
    public function __construct(private AssetDownloadService $assetDownloadService)
    {
    }

    public function __invoke(CountZipImagesPayload $payload): CountZipImagesResult
    {
        $asset = Asset::getById($payload->id) ?? throw new AssetNotFoundException($payload->id);

        if (!$asset->isAllowed('view')) {
            return new CountZipImagesResult(0);
        }

        $assetList = $this->assetDownloadService->getZipImageListing($asset, $payload->selectedIds);

        return new CountZipImagesResult($assetList->getTotalCount());
    }
}
