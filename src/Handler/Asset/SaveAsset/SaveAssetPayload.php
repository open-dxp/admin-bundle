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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\SaveAsset;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class SaveAssetPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $id,
        public readonly string $task,
        public readonly ?array $metadata,
        public readonly ?array $propertiesData,
        public readonly ?array $schedulerData,
        public readonly ?string $rawData,
        public readonly bool $hasImage,
        public readonly ?array $imageData,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        $hasImage = $request->request->has('image');

        $metadata = $request->request->has('metadata')
            ? json_decode($request->request->getString('metadata'), true)
            : null;
        $propertiesData = $request->request->has('properties')
            ? json_decode($request->request->getString('properties'), true)
            : null;
        $schedulerData = null;
        if ($request->request->has('scheduler')) {
            $decodedScheduler = json_decode($request->request->getString('scheduler'), true);
            // a present-but-non-array value (e.g. JSON null) must still clear existing
            // scheduled tasks, matching ApplySchedulerDataTrait's original behavior
            $schedulerData = is_array($decodedScheduler) ? $decodedScheduler : [];
        }
        $imageData = $hasImage
            ? json_decode($request->request->getString('image'), true)
            : null;
        $rawData = $request->request->get('data');

        return new static(
            id:            (int) $request->request->getString('id'),
            task:          $request->request->getString('task'),
            metadata:      is_array($metadata) ? $metadata : null,
            propertiesData: is_array($propertiesData) ? $propertiesData : null,
            schedulerData: $schedulerData,
            rawData:       $rawData !== null ? (string) $rawData : null,
            hasImage:      $hasImage,
            imageData:     is_array($imageData) ? $imageData : null,
        );
    }
}
