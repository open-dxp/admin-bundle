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

namespace OpenDxp\Bundle\AdminBundle\Handler\Translation\UploadTranslationImportFile;

use OpenDxp\Bundle\AdminBundle\Session\Gateway\TranslationImportSessionGateway;
use OpenDxp\Tool\Admin as AdminTool;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class UploadTranslationImportFileHandler
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly TranslationImportSessionGateway $translationImportSession,
    ) {
    }

    public function __invoke(UploadTranslationImportFilePayload $payload): UploadTranslationImportFileResult
    {
        if ($payload->file === null) {
            throw new BadRequestHttpException('No file uploaded.');
        }

        $tmpData = file_get_contents($payload->file->getPathname()) ?: '';

        $filename = uniqid('import_translations-', false);
        $importFile = OPENDXP_SYSTEM_TEMP_DIRECTORY . '/' . $filename;
        $this->filesystem->dumpFile($importFile, $tmpData);

        $dialect = AdminTool::determineCsvDialect($importFile);

        if (!empty($dialect->lineterminator) && empty(preg_match('/[a-f0-9]{2}/i', $dialect->lineterminator))) {
            $dialect->lineterminator = bin2hex($dialect->lineterminator);
        }

        $this->translationImportSession->storeImportFile($importFile);

        return new UploadTranslationImportFileResult(
            config: ['csvSettings' => $dialect],
        );
    }
}
