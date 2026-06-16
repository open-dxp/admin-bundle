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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper;

use League\Flysystem\UnableToReadFile;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridColumnConfigService;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridExportService;
use OpenDxp\Logger;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Element;
use OpenDxp\Tool\Storage;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class DoAssetExportHandler
{
    public function __construct(
        private readonly GridExportService $gridExportService,
        private readonly GridColumnConfigService $gridColumnConfigService,
    ) {}

    public function __invoke(
        string $fileHandle,
        array $ids,
        string $delimiter,
        string $language,
        string $header,
        array $fields,
        bool $addTitles,
    ): void {
        $list = new Asset\Listing();

        $quotedIds = [];
        foreach ($ids as $id) {
            $quotedIds[] = $list->quote($id);
        }

        $list->setCondition('id IN (' . implode(',', $quotedIds) . ')');
        $list->setOrderKey(' FIELD(id, ' . implode(',', $quotedIds) . ')', false);

        $csv = $this->buildCsvData($language, $list, $fields, $header, $addTitles);

        $temp = tmpfile();

        try {
            $storage = Storage::get('temp');
            $csvFile = $this->gridExportService->getCsvFile($fileHandle);

            $fileStream = $storage->readStream($csvFile);
            stream_copy_to_stream($fileStream, $temp, null, 0);

            $firstLine = !($addTitles && $header === 'no_header');

            foreach ($csv as $line) {
                if ($addTitles && $firstLine) {
                    $firstLine = false;
                    fwrite($temp, implode($delimiter, $line) . "\r\n");
                } else {
                    fwrite($temp, implode($delimiter, array_map($this->gridColumnConfigService->encode(...), $line)) . "\r\n");
                }
            }

            $storage->writeStream($csvFile, $temp);
        } catch (UnableToReadFile $exception) {
            Logger::err($exception->getMessage());
            throw new BadRequestHttpException(sprintf('export file not found: %s', $fileHandle));
        } finally {
            if (is_resource($temp)) {
                fclose($temp);
            }
        }
    }

    private function buildCsvData(
        string $language,
        Asset\Listing $list,
        array $fields,
        string $header,
        bool $addTitles,
    ): array {
        $csv = [];
        $unsupportedFields = ['preview~system', 'size~system'];
        $fields = array_filter($fields, fn ($field) => !in_array($field['key'], $unsupportedFields));

        if ($addTitles && $header !== 'no_header') {
            $columns = $fields;
            $titleIdx = $header === 'name' ? 'key' : 'label';
            foreach ($columns as $columnIdx => $columnKeys) {
                $columns[$columnIdx] = '"' . $columnKeys[$titleIdx] . '"';
            }
            $csv[] = $columns;
        }

        foreach ($list->load() as $asset) {
            if ($fields) {
                $dataRows = [];
                foreach ($fields as $field) {
                    $fieldDef = explode('~', $field['key']);
                    $getter = 'get' . ucfirst($fieldDef[0]);

                    if (isset($fieldDef[1])) {
                        if ($fieldDef[1] === 'system' && method_exists($asset, $getter)) {
                            $data = $asset->$getter($language);
                        } else {
                            $fieldDef[1] = str_replace('none', '', $fieldDef[1]);
                            $data = $asset->getMetadata($fieldDef[0], $fieldDef[1], true);
                        }
                    } else {
                        $data = $asset->getMetadata($field['key'], $language, true);
                    }

                    if ($data instanceof Element\ElementInterface) {
                        $data = $data->getRealFullPath();
                    }
                    $dataRows[] = $data;
                }
                $dataRows = Element\Service::escapeCsvRecord($dataRows);
                $csv[] = $dataRows;
            }
        }

        return $csv;
    }
}
