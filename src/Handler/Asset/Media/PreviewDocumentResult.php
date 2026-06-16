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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Media;

use OpenDxp\Model\Asset;
use OpenDxp\Model\Asset\Enum\PdfScanStatus;

final readonly class PreviewDocumentResult
{
    public function __construct(
        public Asset\Document $asset,
        public ?PdfScanStatus $scanStatus,
        /** non-null means render "open in new tab" template with this thumbnail path */
        public ?string $thumbnailPath,
        public string $assetPath,
        /** non-null means stream the PDF directly */
        public mixed $stream,
    ) {}
}
