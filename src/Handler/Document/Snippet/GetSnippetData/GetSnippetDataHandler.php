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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Snippet\GetSnippetData;

use OpenDxp\Bundle\AdminBundle\Enricher\Document\DocumentMetaEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Document\DraftEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Document\PropertiesEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Document\TranslationEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\AdminStyleEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\UserNamesEnricher;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Helper\DocumentVersionHelper;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdQueryPayload;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Element\EditLockService;
use OpenDxp\Model\Document\Snippet;
use OpenDxp\Model\Element;
use OpenDxp\Model\Schedule\Task;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetSnippetDataHandler
{
    public function __construct(
        private readonly EditLockService $editLockService,
        private readonly AdminUserContextInterface $userContext,
        private readonly DocumentMetaEnricher $documentMetaEnricher,
        private readonly AdminStyleEnricher $adminStyleEnricher,
        private readonly UserNamesEnricher $userNamesEnricher,
        private readonly PropertiesEnricher $propertiesEnricher,
        private readonly TranslationEnricher $translationEnricher,
        private readonly DraftEnricher $draftEnricher,
    ) {}

    public function __invoke(IdQueryPayload $payload): GetSnippetDataResult
    {
        $snippet = Snippet::getById($payload->id);
        if (!$snippet) {
            throw new NotFoundHttpException('Snippet not found');
        }

        if ($snippet->isAllowed('save') || $snippet->isAllowed('publish') || $snippet->isAllowed('unpublish') || $snippet->isAllowed('delete')) {
            $this->editLockService->checkAndAcquire($snippet->getId(), 'document', AdminEvents::DOCUMENT_GET_IS_LOCKED, $snippet);
        }

        $snippet = clone $snippet;
        $draftVersion = null;
        $snippet = DocumentVersionHelper::resolveLatestDraft($snippet, $draftVersion, $this->userContext->getAdminUser()?->getId());

        $versions = Element\Service::getSafeVersionInfo($snippet->getVersions());
        $snippet->setVersions(array_splice($versions, -1, 1));
        $snippet->setParent(null);
        $snippet->setEditables(null);

        $data = $snippet->getObjectVars();
        $data['locked'] = $snippet->isLocked();
        $data['url'] = $snippet->getUrl();
        $data['scheduledTasks'] = array_map(
            static fn (Task $task) => $task->getObjectVars(),
            $snippet->getScheduledTasks()
        );

        if ($snippet->getContentMainDocument()) {
            $data['contentMainDocumentPath'] = $snippet->getContentMainDocument()->getRealFullPath();
        }

        $this->documentMetaEnricher->enrich($snippet, $data);
        $this->adminStyleEnricher->forEditor($snippet, $data);
        $this->userNamesEnricher->enrich($snippet, $data);
        $this->propertiesEnricher->enrich($snippet, $data);
        $this->translationEnricher->enrich($snippet, $data);
        $this->draftEnricher->enrich($snippet, $data, $draftVersion);

        return new GetSnippetDataResult(snippet: $snippet, data: $data, draftVersion: $draftVersion);
    }
}
