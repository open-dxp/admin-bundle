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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Page\GetPageData;

use OpenDxp\Bundle\AdminBundle\Enricher\Document\DocumentMetaEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Document\DraftEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Document\PropertiesEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Document\TranslationEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\AdminStyleEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\PhpMetaEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\PreSendDataEventEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\UserNamesEnricher;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Helper\DocumentVersionHelper;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Element\EditLockService;
use OpenDxp\Document\StaticPageGenerator;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element;
use OpenDxp\Model\Schedule\Task;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetPageDataHandler
{
    public function __construct(
        private readonly StaticPageGenerator $staticPageGenerator,
        private readonly EditLockService $editLockService,
        private readonly AdminUserContextInterface $userContext,
        private readonly DocumentMetaEnricher $documentMetaEnricher,
        private readonly AdminStyleEnricher $adminStyleEnricher,
        private readonly UserNamesEnricher $userNamesEnricher,
        private readonly PropertiesEnricher $propertiesEnricher,
        private readonly TranslationEnricher $translationEnricher,
        private readonly DraftEnricher $draftEnricher,
        private readonly PhpMetaEnricher $phpMetaEnricher,
        private readonly PreSendDataEventEnricher $preSendDataEventEnricher,
    ) {
    }

    public function __invoke(GetPageDataPayload $payload): GetPageDataResult
    {
        $page = Document\Page::getById($payload->id);
        if (!$page) {
            throw new NotFoundHttpException('Page not found');
        }

        if (!$page->isAllowed('view')) {
            throw new AccessDeniedHttpException();
        }

        if ($page->isAllowed('save') || $page->isAllowed('publish') || $page->isAllowed('unpublish') || $page->isAllowed('delete')) {
            $this->editLockService->checkAndAcquire($page->getId(), 'document', AdminEvents::DOCUMENT_GET_IS_LOCKED, $page);
        }

        $page = clone $page;
        $draftVersion = null;
        $page = DocumentVersionHelper::resolveLatestDraft($page, $draftVersion, $this->userContext->getAdminUser()?->getId());

        $pageVersions = Element\Service::getSafeVersionInfo($page->getVersions());
        $page->setVersions(array_splice($pageVersions, -1, 1));
        $page->setParent(null);

        // unset useless data
        $page->setEditables(null);
        $page->setChildren(null);

        $data = $page->getObjectVars();
        $data['locked'] = $page->isLocked();

        if ($page->getContentMainDocument()) {
            $data['contentMainDocumentPath'] = $page->getContentMainDocument()->getRealFullPath();
        }

        if ($page->getStaticGeneratorEnabled()) {
            $data['staticLastGenerated'] = $this->staticPageGenerator->getLastModified($page);
        }

        $data['url'] = $page->getUrl();
        $data['scheduledTasks'] = array_map(
            static fn (Task $task) => $task->getObjectVars(),
            $page->getScheduledTasks()
        );

        $this->documentMetaEnricher->enrich($page, $data);
        $this->phpMetaEnricher->enrich($page, $data);
        $this->adminStyleEnricher->forEditor($page, $data);
        $this->userNamesEnricher->enrich($page, $data);
        $this->propertiesEnricher->enrich($page, $data);
        $this->translationEnricher->enrich($page, $data);
        $this->draftEnricher->enrich($page, $data, $draftVersion);

        $this->preSendDataEventEnricher->enrich($page, $data);

        return new GetPageDataResult(data: $data);
    }
}
