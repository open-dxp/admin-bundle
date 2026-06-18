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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetDocumentThumbnail;

use OpenDxp\Bundle\AdminBundle\Exception\Asset\AssetNotFoundException;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetDocumentThumbnail\GetDocumentThumbnailPayload;
use OpenDxp\Messenger\AssetPreviewImageMessage;
use OpenDxp\Model\Asset;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;

final class GetDocumentThumbnailHandler
{
    public function __construct(private readonly MessageBusInterface $messageBus) {}

    public function __invoke(GetDocumentThumbnailPayload $payload): GetDocumentThumbnailResult
    {
        $id = $payload->id;
        $hasThumbnailPreview = $payload->hasThumbnailPreview;
        $page = $payload->page;
        $origin = $payload->origin;
        $queryAll = $payload->queryAll;
        $document = Asset\Document::getById($id) ?? throw new AssetNotFoundException($id);

        if (!$document->isAllowed('view')) {
            throw new AccessDeniedHttpException('not allowed to view thumbnail');
        }

        $thumbnail = Asset\Image\Thumbnail\Config::getByAutoDetect($queryAll);

        $format = strtolower($thumbnail->getFormat());
        if ($format === 'source') {
            $thumbnail->setFormat('jpeg');
        }

        if ($hasThumbnailPreview) {
            $thumbnail = Asset\Image\Thumbnail\Config::getPreviewConfig();
        }

        $thumb = $document->getImageThumbnail($thumbnail, $page ?? 1);

        if ($origin === 'treeNode' && !$thumb->exists()) {
            $this->messageBus->dispatch(new AssetPreviewImageMessage($document->getId()));
            throw new NotFoundHttpException(sprintf('Tree preview thumbnail not available for asset %s', $document->getId()));
        }

        return new GetDocumentThumbnailResult($thumb->getStream(), $thumb->getFileExtension());
    }
}
