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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\Document;

use Exception;
use OpenDxp;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Handler\Document\ChangeMainDocumentHandler;
use OpenDxp\Bundle\AdminBundle\Payload\Document\EmailPayload;
use OpenDxp\Bundle\AdminBundle\Payload\Document\FolderPayload;
use OpenDxp\Bundle\AdminBundle\Payload\Document\HardlinkPayload;
use OpenDxp\Bundle\AdminBundle\Payload\Document\LinkPayload;
use OpenDxp\Bundle\AdminBundle\Payload\Document\PagePayload;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Bundle\AdminBundle\Service\Document\DocumentPayloadMapper;
use OpenDxp\Bundle\AdminBundle\Service\Element\SessionService;
use OpenDxp\Bundle\AdminBundle\Service\ElementServiceInterface;
use OpenDxp\Controller\Traits\ElementEditLockHelperTrait;
use OpenDxp\Model;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\ElementInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[IsGranted(CorePermission::Documents->value)]
abstract class DocumentControllerBase extends AdminAbstractController
{
    use ElementEditLockHelperTrait;

    public const string TASK_PUBLISH = 'publish';

    public const string TASK_UNPUBLISH = 'unpublish';

    public const string TASK_SAVE = 'save';

    public const string TASK_VERSION = 'version';

    public const string TASK_SCHEDULER = 'scheduler';

    public const string TASK_AUTOSAVE = 'autosave';

    public const string TASK_DELETE = 'delete';

    public function __construct(
        protected ElementServiceInterface $elementService,
        protected readonly SessionService $sessionService,
        protected readonly DocumentPayloadMapper $mapper,
    ) {}

    #[Route('/save-to-session', name: 'savetosession', methods: ['POST'])]
    public function saveToSessionAction(Request $request): JsonResponse
    {
        if (!($documentId = (int) $request->request->get('id'))) {
            return $this->adminJson(ApiResponse::ok());
        }

        $document = $this->sessionService->getOrLoadDocument($documentId);
        if (!$document) {
            throw $this->createNotFoundException();
        }

        $document->setInDumpState(true);

        if ($document instanceof Document\Email) {
            $this->mapper->applyPagePayload(EmailPayload::fromRequest($request), $document);
        } elseif ($document instanceof Document\PageSnippet) {
            $this->mapper->applyPagePayload(PagePayload::fromRequest($request), $document);
        } elseif ($document instanceof Document\Link) {
            $this->mapper->applyLinkPayload(LinkPayload::fromRequest($request), $document);
        } elseif ($document instanceof Document\Hardlink) {
            $this->mapper->applyHardlinkPayload(HardlinkPayload::fromRequest($request), $document);
        } elseif ($document instanceof Document\Folder) {
            $this->mapper->applyFolderPayload(FolderPayload::fromRequest($request), $document);
        }

        $this->sessionService->saveDocument($document);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/remove-from-session', name: 'removefromsession', methods: ['DELETE'])]
    public function removeFromSessionAction(Request $request): JsonResponse
    {
        $this->sessionService->removeDocument((int) $request->request->get('id'));

        return $this->adminJson(ApiResponse::ok());
    }

    /**
     * This is used for pages and snippets to change the main document (which is not saved with the normal save button)
     */
    #[Route('/change-main-document', name: 'changemaindocument', methods: ['PUT'])]
    public function changeMainDocumentAction(Request $request, ChangeMainDocumentHandler $changeMainDocument): JsonResponse
    {
        $changeMainDocument(
            (int) $request->request->get('id'),
            (string) $request->request->get('contentMainDocumentPath'),
        );

        return $this->adminJson(ApiResponse::ok());
    }

    public function getTreeNodeConfig(ElementInterface $element): array
    {
        return $this->elementService->getElementTreeNodeConfig($element);
    }

    /**
     * @throws Exception
     */
    protected function preSendDataActions(array $data, Model\Document $document): JsonResponse
    {
        $event = new GenericEvent($this, [
            'data' => $data,
            'document' => $document,
        ]);
        OpenDxp::getEventDispatcher()->dispatch($event, AdminEvents::DOCUMENT_GET_PRE_SEND_DATA);
        $data = $event->getArgument('data');

        if ($document->isAllowed('view')) {
            return $this->adminJson($data);
        }

        throw $this->createAccessDeniedHttpException();
    }
}
