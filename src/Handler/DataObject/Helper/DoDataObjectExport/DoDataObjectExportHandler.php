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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\DoDataObjectExport;

use InvalidArgumentException;
use League\Flysystem\UnableToReadFile;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Service\Admin\CurrentControllerContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridColumnConfigService;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridExportService;
use OpenDxp\Logger;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\Listing;
use OpenDxp\Tool\Storage;
use OpenDxp\Tool\UserTimezone;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class DoDataObjectExportHandler
{
    public function __construct(
        private readonly GridExportService $gridExportService,
        private readonly GridColumnConfigService $gridColumnConfigService,
        private readonly LocaleServiceInterface $localeService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CurrentControllerContextInterface $currentControllerContext,
    ) {}

    public function __invoke(DoDataObjectExportPayload $payload): void
    {
        UserTimezone::setUserTimezone($payload->userTimezone);
        DataObject\Concrete::setGetInheritedValues($payload->enableInheritance);

        $class = DataObject\ClassDefinition::getById($payload->classId);
        if (!$class) {
            throw new InvalidArgumentException('No class definition found');
        }

        $listClass = '\\OpenDxp\\Model\\DataObject\\' . ucfirst($class->getName()) . '\\Listing';

        /** @var Listing $list */
        $list = new $listClass();

        $quotedIds = [];
        foreach ($payload->ids as $id) {
            $quotedIds[] = $list->quote($id);
        }

        $list->setObjectTypes(DataObject::$types);
        $list->setCondition('id IN (' . implode(',', $quotedIds) . ')');
        $list->setOrderKey(' FIELD(id, ' . implode(',', $quotedIds) . ')', false);

        $beforeListExportEvent = new GenericEvent($this->currentControllerContext->getController(), [
            'list' => $list,
            'context' => $payload->allParams,
        ]);
        $this->eventDispatcher->dispatch($beforeListExportEvent, AdminEvents::OBJECT_LIST_BEFORE_EXPORT);
        $list = $beforeListExportEvent->getArgument('list');

        $csv = DataObject\Service::getCsvData(
            $payload->requestedLanguage,
            $this->localeService,
            $list,
            $payload->fields,
            $payload->header,
            $payload->addTitles,
            $payload->context
        );

        $temp = tmpfile();

        try {
            $storage = Storage::get('temp');
            $csvFile = $this->gridExportService->getCsvFile($payload->fileHandle);

            $fileStream = $storage->readStream($csvFile);
            stream_copy_to_stream($fileStream, $temp, null, 0);

            $firstLine = true;

            if ($payload->addTitles && $payload->header === 'no_header') {
                array_shift($csv);
                $firstLine = false;
            }

            $lineCount = count($csv);

            if (!$payload->addTitles && $lineCount > 0) {
                fwrite($temp, "\r\n");
            }

            for ($i = 0; $i < $lineCount; $i++) {
                $line = $csv[$i];
                if ($payload->addTitles && $firstLine) {
                    $firstLine = false;
                    fwrite($temp, implode($payload->delimiter, $line));
                } else {
                    fwrite($temp, implode($payload->delimiter, array_map($this->gridColumnConfigService->encode(...), $line)));
                }
                if ($i < $lineCount - 1) {
                    fwrite($temp, "\r\n");
                }
            }

            $storage->writeStream($csvFile, $temp);
        } catch (UnableToReadFile $exception) {
            Logger::err($exception->getMessage());
            throw new AdminOperationFailedException(sprintf('export file not found: %s', $payload->fileHandle));
        } finally {
            if (is_resource($temp)) {
                fclose($temp);
            }
        }
    }
}
