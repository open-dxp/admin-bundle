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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Helper\GridHelperService;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridExportService;
use OpenDxp\Tool\Storage;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class GetExportJobsHandler
{
    public function __construct(
        private readonly GridHelperService $gridHelperService,
        private readonly GridExportService $gridExportService,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(GetExportJobsPayload $payload): GetExportJobsResult
    {
        $allParams = $payload->allParams;
        $fieldnames = [];
        $fields = json_decode($allParams['fields'][0], true);
        foreach ($fields as $field) {
            $fieldnames[] = $field['key'];
        }
        $allParams['fields'] = $fieldnames;

        $list = $this->gridHelperService->prepareListingForGrid($allParams, $payload->requestedLanguage);

        $beforeListPrepareEvent = new GenericEvent($this, [
            'list' => $list,
            'context' => $allParams,
        ]);
        $this->eventDispatcher->dispatch($beforeListPrepareEvent, AdminEvents::OBJECT_LIST_BEFORE_EXPORT_PREPARE);
        $list = $beforeListPrepareEvent->getArgument('list');

        $ids = $list->loadIdList();
        $jobs = array_chunk($ids, 20);

        $fileHandle = uniqid('export-');
        Storage::get('temp')->write($this->gridExportService->getCsvFile($fileHandle), '');

        return new GetExportJobsResult(jobs: $jobs, fileHandle: $fileHandle);
    }
}
