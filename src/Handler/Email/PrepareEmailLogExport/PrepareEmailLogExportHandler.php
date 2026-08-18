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

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\PrepareEmailLogExport;

use OpenDxp\Bundle\AdminBundle\Service\Email\EmailLogListingFactory;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridExportService;
use OpenDxp\Tool\Storage;

final class PrepareEmailLogExportHandler
{
    public function __construct(
        private readonly EmailLogListingFactory $listingFactory,
        private readonly GridExportService $gridExportService,
    ) {
    }

    public function __invoke(PrepareEmailLogExportPayload $payload): PrepareEmailLogExportResult
    {
        $list = $this->listingFactory->create($payload->documentId, $payload->filter, $payload->ids);

        $fileHandle = uniqid('email-log-export-', false);
        Storage::get('temp')->write($this->gridExportService->getCsvFile($fileHandle), '');

        return new PrepareEmailLogExportResult(fileHandle: $fileHandle, total: $list->getTotalCount());
    }
}
