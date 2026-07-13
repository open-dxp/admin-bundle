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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Page\SavePage;

use OpenDxp\Bundle\AdminBundle\Helper\DocumentVersionHelper;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\SavePage\SavePagePayload;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Document\DocumentPayloadMapper;
use OpenDxp\Bundle\AdminBundle\Service\Document\DocumentPersistenceCoordinator;
use OpenDxp\Bundle\AdminBundle\Service\Element\ElementDraftService;
use OpenDxp\Document\StaticPageGenerator;
use OpenDxp\Event\DocumentEvents;
use OpenDxp\Event\Model\DocumentEvent;
use OpenDxp\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use OpenDxp\Model\Document\Page;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class SavePageHandler
{
    use RecursionBlockingEventDispatchHelperTrait;

    public function __construct(
        private readonly ElementDraftService $elementDraftService,
        private readonly DocumentPayloadMapper $mapper,
        private readonly DocumentPersistenceCoordinator $coordinator,
        private readonly AdminUserContextInterface $userContext,
        private readonly StaticPageGenerator $staticPageGenerator,
    ) {}

    public function __invoke(SavePagePayload $payload, bool $sessionAware = true): SavePagePublishedResult|SavePageDraftResult
    {
        $oldPage = Page::getById($payload->id);
        if (!$oldPage) {
            throw new NotFoundHttpException('Page not found');
        }

        if ($sessionAware) {
            $sessionPage = $this->elementDraftService->getDocument($oldPage);
            if ($sessionPage instanceof Page) {
                $page = $sessionPage;
            } else {
                $page = DocumentVersionHelper::resolveLatestDraft($oldPage, userId: $this->userContext->getAdminUser()?->getId());
            }
        } else {
            $page = $oldPage;
        }

        $this->mapper->applyPagePayload($payload, $page);

        $result = $this->coordinator->save($page, $payload->task);

        $this->dispatchEvent(
            new DocumentEvent($result->document, ['oldPage' => $oldPage, 'task' => $result->task]),
            DocumentEvents::PAGE_POST_SAVE_ACTION,
        );

        if ($sessionAware && !in_array($payload->task, ['publish', 'unpublish'], true)) {
            $this->elementDraftService->saveDocument($result->document);
        }

        $savedPage = $result->document instanceof Page ? $result->document : $page;

        if ($result->task === 'publish' || $result->task === 'unpublish') {
            $data = [
                'versionDate' => $savedPage->getModificationDate(),
                'versionCount' => $savedPage->getVersionCount(),
            ];
            if ($staticGeneratorEnabled = $savedPage->getStaticGeneratorEnabled()) {
                $data['staticGeneratorEnabled'] = $staticGeneratorEnabled;
                $data['staticLastGenerated'] = $this->staticPageGenerator->getLastModified($savedPage);
            }

            return new SavePagePublishedResult(data: $data, treeData: $result->treeData);
        }

        $draft = [];
        if ($result->version) {
            $draft = [
                'id' => $result->version->getId(),
                'modificationDate' => $result->version->getDate(),
                'isAutoSave' => $result->version->isAutoSave(),
            ];
        }

        return new SavePageDraftResult(treeData: $result->treeData, draft: $draft);
    }
}
