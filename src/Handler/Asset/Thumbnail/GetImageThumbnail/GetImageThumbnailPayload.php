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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetImageThumbnail;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetImageThumbnailPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $id = 0,
        public readonly bool $hasFileinfo = false,
        public readonly ?array $thumbnailParam = null,
        public readonly ?array $configDecoded = null,
        public readonly array $queryAll = [],
        public readonly bool $hasThumbnailPreview = false,
        public readonly ?string $origin = null,
        public readonly bool $hasCropPercent = false,
        public readonly ?string $cropWidth = null,
        public readonly ?string $cropHeight = null,
        public readonly ?string $cropTop = null,
        public readonly ?string $cropLeft = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $config = $request->query->getString('config') ?: null;
        $cropPercent = $request->query->getString('cropPercent') ?: null;
        $decodedConfig = $config !== null ? json_decode($config, true) : null;

        return new static(
            id:                  $request->query->getInt('id'),
            hasFileinfo:         $request->query->has('fileinfo'),
            thumbnailParam:      $request->query->all('thumbnail') ?: null,
            configDecoded:       is_array($decodedConfig) ? $decodedConfig : null,
            queryAll:            $request->query->all(),
            hasThumbnailPreview: $request->query->has('treepreview'),
            origin:              $request->query->getString('origin') ?: null,
            hasCropPercent:      $cropPercent !== null && filter_var($cropPercent, FILTER_VALIDATE_BOOLEAN),
            cropWidth:           $request->query->getString('cropWidth') ?: null,
            cropHeight:          $request->query->getString('cropHeight') ?: null,
            cropTop:             $request->query->getString('cropTop') ?: null,
            cropLeft:            $request->query->getString('cropLeft') ?: null,
        );
    }
}
