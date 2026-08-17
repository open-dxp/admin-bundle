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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\GetDocumentData;

use OpenDxp\Bundle\AdminBundle\Enricher\Document\DocumentMetaEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Document\PropertiesEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Document\TranslationEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\AdminStyleEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\PhpMetaEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\PreSendDataEventEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\UserNamesEnricher;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Exception\Document\DocumentNotFoundException;
use OpenDxp\Bundle\AdminBundle\Service\Element\EditLockService;
use OpenDxp\Model\Document;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class GetDocumentDataHandler
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

    public function __invoke(GetDocumentDataPayload $payload): GetDocumentDataResult
    {
        $document = Document::getById($payload->id);
        if (!$document instanceof Document) {
            throw new DocumentNotFoundException($payload->id);
        }

        if (!$document->isAllowed('view')) {
            throw new AccessDeniedHttpException();
        }

        if (
            $document->isAllowed('save') ||
            $document->isAllowed('publish') ||
            $document->isAllowed('unpublish') ||
            $document->isAllowed('delete')
        ) {
            $this->editLockService->checkAndAcquire($document->getId(), 'document', AdminEvents::DOCUMENT_GET_IS_LOCKED, $document);
        }

        $document = clone $document;
        $data = $document->getObjectVars();

        $this->documentMetaEnricher->enrich($document, $data);
        $this->phpMetaEnricher->enrich($document, $data);
        $this->adminStyleEnricher->forEditor($document, $data);
        $this->userNamesEnricher->enrich($document, $data);
        $this->propertiesEnricher->enrich($document, $data);
        $this->translationEnricher->enrich($document, $data);

        $this->preSendDataEventEnricher->enrich($document, $data);

        return new GetDocumentDataResult($data);
    }
}
