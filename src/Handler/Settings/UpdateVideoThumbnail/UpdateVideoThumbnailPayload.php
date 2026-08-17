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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings\UpdateVideoThumbnail;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class UpdateVideoThumbnailPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly string $name,
        public readonly array $settingsData,
        public readonly array $mediaData,
        public readonly array $mediaOrder,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            name:         $request->request->getString('name'),
            settingsData: json_decode($request->request->getString('settings'), true) ?? [],
            mediaData:    json_decode($request->request->getString('medias'), true) ?? [],
            mediaOrder:   json_decode($request->request->getString('mediaOrder'), true) ?? [],
        );
    }
}
