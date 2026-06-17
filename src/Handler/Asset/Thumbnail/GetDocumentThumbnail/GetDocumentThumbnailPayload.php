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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Thumbnail\GetDocumentThumbnail;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetDocumentThumbnailPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $id = 0,
        public readonly bool $hasThumbnailPreview = false,
        public readonly ?int $page = null,
        public readonly ?string $origin = null,
        public readonly array $queryAll = [],
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            id:                  $request->query->getInt('id'),
            hasThumbnailPreview: $request->query->has('treepreview'),
            page:                $request->query->has('page') ? $request->query->getInt('page') : null,
            origin:              $request->query->getString('origin') ?: null,
            queryAll:            $request->query->all(),
        );
    }
}
