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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\ImportUpload;

use OpenDxp\Tool\Text;
use Symfony\Component\Filesystem\Filesystem;

final class ImportUploadHandler
{
    public function __construct(private readonly Filesystem $filesystem) {}

    public function __invoke(ImportUploadPayload $payload): void
    {
        $data = Text::convertToUTF8($payload->fileContents);
        $importId = str_replace('..', '', $payload->importId);

        $importFile = OPENDXP_SYSTEM_TEMP_DIRECTORY . '/import_' . $importId;
        $this->filesystem->dumpFile($importFile, $data);

        $importFileOriginal = OPENDXP_SYSTEM_TEMP_DIRECTORY . '/import_' . $importId . '_original';
        $this->filesystem->dumpFile($importFileOriginal, $data);
    }
}
