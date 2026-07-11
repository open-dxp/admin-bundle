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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\DownloadImageThumbnail;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class DownloadImageThumbnailPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $id = 0,
        public readonly ?string $thumbnailName = null,
        public readonly ?string $config = null,
        public readonly ?array $configData = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $thumbnail = $request->query->getString('thumbnail') ?: null;
        $config = $request->query->getString('config') ?: null;
        $type = $request->query->getString('type') ?: null;

        $configData = null;
        if ($config !== null) {
            $decoded = json_decode($config, true);
            $configData = is_array($decoded) ? $decoded : null;
        } elseif ($type !== null) {
            $predefined = [
                'web'    => ['resize_mode' => 'scaleByWidth', 'width' => 3500, 'dpi' => 72,  'format' => 'JPEG', 'quality' => 85],
                'print'  => ['resize_mode' => 'scaleByWidth', 'width' => 6000, 'dpi' => 300, 'format' => 'JPEG', 'quality' => 95],
                'office' => ['resize_mode' => 'scaleByWidth', 'width' => 1190, 'dpi' => 144, 'format' => 'JPEG', 'quality' => 90],
            ];
            $configData = $predefined[$type] ?? null;
        }

        return new static(
            id:            $request->query->getInt('id'),
            thumbnailName: $thumbnail,
            config:        $config,
            configData:    $configData,
        );
    }
}
