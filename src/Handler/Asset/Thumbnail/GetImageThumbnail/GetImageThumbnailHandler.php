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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetImageThumbnail;

use OpenDxp\Bundle\AdminBundle\Resolver\Asset\ImageThumbnailResolver;

final class GetImageThumbnailHandler
{
    public function __construct(private readonly ImageThumbnailResolver $resolver)
    {
    }

    public function __invoke(GetImageThumbnailPayload $payload): GetImageThumbnailResult
    {
        $resolution = $this->resolver->resolve($payload);

        return new GetImageThumbnailResult($resolution->image, $resolution->thumbnailResult);
    }
}
