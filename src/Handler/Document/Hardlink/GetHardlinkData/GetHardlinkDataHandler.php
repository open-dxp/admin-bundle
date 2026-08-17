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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Hardlink\GetHardlinkData;

use OpenDxp\Bundle\AdminBundle\Enricher\Document\DocumentMetaEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Document\PropertiesEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Document\TranslationEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\AdminStyleEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\PhpMetaEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\PreSendDataEventEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\UserNamesEnricher;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdQueryPayload;
use OpenDxp\Bundle\AdminBundle\Service\Element\EditLockService;
use OpenDxp\Model\Document\Hardlink;
use OpenDxp\Model\Schedule\Task;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetHardlinkDataHandler
{
    public function __construct(
        private readonly EditLockService $editLockService,
        private readonly DocumentMetaEnricher $documentMetaEnricher,
        private readonly AdminStyleEnricher $adminStyleEnricher,
        private readonly UserNamesEnricher $userNamesEnricher,
        private readonly PropertiesEnricher $propertiesEnricher,
        private readonly TranslationEnricher $translationEnricher,
        private readonly PhpMetaEnricher $phpMetaEnricher,
        private readonly PreSendDataEventEnricher $preSendDataEventEnricher,
    ) {
    }

    public function __invoke(IdQueryPayload $payload): GetHardlinkDataResult
    {
        $link = Hardlink::getById($payload->id);
        if (!$link) {
            throw new NotFoundHttpException('Hardlink not found');
        }

        if (!$link->isAllowed('view')) {
            throw new AccessDeniedHttpException();
        }

        if ($link->isAllowed('save') || $link->isAllowed('publish') || $link->isAllowed('unpublish') || $link->isAllowed('delete')) {
            $this->editLockService->checkAndAcquire($link->getId(), 'document', AdminEvents::DOCUMENT_GET_IS_LOCKED, $link);
        }

        $cloned = clone $link;
        $cloned->setParent(null);

        $data = $cloned->getObjectVars();
        $data['locked'] = $cloned->isLocked();
        $data['scheduledTasks'] = array_map(
            static fn (Task $task) => $task->getObjectVars(),
            $cloned->getScheduledTasks()
        );

        if ($cloned->getSourceDocument()) {
            $data['sourcePath'] = $cloned->getSourceDocument()->getRealFullPath();
        }

        $this->documentMetaEnricher->enrich($cloned, $data);
        $this->phpMetaEnricher->enrich($cloned, $data);
        $this->adminStyleEnricher->forEditor($cloned, $data);
        $this->userNamesEnricher->enrich($cloned, $data);
        $this->propertiesEnricher->enrich($cloned, $data);
        $this->translationEnricher->enrich($cloned, $data);

        $this->preSendDataEventEnricher->enrich($cloned, $data);

        return new GetHardlinkDataResult(data: $data);
    }
}
