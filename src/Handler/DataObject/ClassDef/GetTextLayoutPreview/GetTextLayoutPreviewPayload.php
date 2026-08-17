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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetTextLayoutPreview;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetTextLayoutPreviewPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public string $objPath = '',
        public ?string $className = null,
        public string $renderingData = '',
        public ?string $renderingClass = null,
        public ?string $html = null,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            objPath: $request->query->getString('previewObject'),
            className: $request->query->getString('className') ?: null,
            renderingData: $request->query->getString('renderingData'),
            renderingClass: $request->query->getString('renderingClass') ?: null,
            html: $request->query->getString('html') ?: null,
        );
    }
}
