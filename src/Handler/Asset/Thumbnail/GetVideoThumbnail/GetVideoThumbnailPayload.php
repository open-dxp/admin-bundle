<?php

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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetVideoThumbnail;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetVideoThumbnailPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $path = null,
        public readonly bool $hasThumbnailPreview = false,
        public readonly bool $hasSetTime = false,
        public readonly bool $hasSetImage = false,
        public readonly bool $hasImage = false,
        public readonly int $imageId = 0,
        public readonly ?string $time = null,
        public readonly ?string $origin = null,
        public readonly array $queryAll = [],
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            id:                 $request->query->has('id') ? $request->query->getInt('id') : null,
            path:               $request->query->getString('path') ?: null,
            hasThumbnailPreview: $request->query->has('treepreview'),
            hasSetTime:          $request->query->has('settime'),
            hasSetImage:         $request->query->has('setimage'),
            hasImage:            $request->query->has('image'),
            imageId:             $request->query->getInt('image'),
            time:                $request->query->getString('time') ?: null,
            origin:              $request->query->getString('origin') ?: null,
            queryAll:            $request->query->all(),
        );
    }
}
