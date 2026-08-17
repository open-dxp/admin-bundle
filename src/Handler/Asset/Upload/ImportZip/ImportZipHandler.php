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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\ImportZip;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Model\Asset;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZipArchive;

final class ImportZipHandler
{
    private const int FILES_PER_JOB = 5;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly RouterInterface $router,
    ) {
    }

    public function __invoke(ImportZipPayload $payload): ImportZipResult
    {
        $parentId = $payload->parentId;
        $uploadedFilePath = $payload->uploadedFilePath;
        $allowOverwrite = $payload->allowOverwrite;
        $asset = Asset::getById($parentId) ?? throw new NotFoundHttpException('Parent asset not found');

        if (!$asset->isAllowed('create')) {
            throw new AccessDeniedHttpException('not allowed to create');
        }

        $jobId = uniqid('', false);
        $zipFile = OPENDXP_SYSTEM_TEMP_DIRECTORY . '/' . $jobId . '.zip';
        copy($uploadedFilePath, $zipFile);

        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new AdminOperationFailedException($this->translator->trans('could_not_open_zip_file', [], 'admin'));
        }

        $numFiles = $zip->numFiles;
        $zip->close();

        $importZipFilesUrl = $this->router->generate('opendxp_admin_asset_importzipfiles');
        $jobAmount = (int) ceil($numFiles / self::FILES_PER_JOB);
        $jobs = [];
        for ($i = 0; $i < $jobAmount; $i++) {
            $jobs[] = [[
                'url' => $importZipFilesUrl,
                'method' => 'POST',
                'params' => [
                    'parentId' => $asset->getId(),
                    'offset' => $i * self::FILES_PER_JOB,
                    'limit' => self::FILES_PER_JOB,
                    'jobId' => $jobId,
                    'last' => (($i + 1) >= $jobAmount) ? 'true' : '',
                    'allowOverwrite' => $allowOverwrite ?: 'false',
                ],
            ]];
        }

        return new ImportZipResult(jobId: $jobId, jobs: $jobs);
    }
}
