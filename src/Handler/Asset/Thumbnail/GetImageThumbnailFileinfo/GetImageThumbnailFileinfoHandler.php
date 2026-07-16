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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetImageThumbnailFileinfo;

use OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetImageThumbnail\GetImageThumbnailPayload;
use OpenDxp\Bundle\AdminBundle\Service\Asset\ImageThumbnailResolver;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetImageThumbnailFileinfoHandler
{
    public function __construct(private readonly ImageThumbnailResolver $resolver) {}

    public function __invoke(GetImageThumbnailPayload $payload): GetImageThumbnailFileinfoResult
    {
        $resolution = $this->resolver->resolve($payload);

        if ($resolution->thumbnailResult === null) {
            throw new NotFoundHttpException(sprintf('Tree preview thumbnail not available for asset %d', $payload->id));
        }

        return new GetImageThumbnailFileinfoResult(
            width: $resolution->thumbnailResult->getWidth(),
            height: $resolution->thumbnailResult->getHeight(),
        );
    }
}
