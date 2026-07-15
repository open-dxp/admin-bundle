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
        private readonly StaticPageGenerator $staticPageGenerator,
    ) {}

    public function __invoke(SavePagePayload $payload): SavePagePublishedResult|SavePageDraftResult
    {
        $loadedPage = Page::getById($payload->id);
        if (!$loadedPage) {
            throw new NotFoundHttpException('Page not found');
        }

        $page = $this->elementDraftService->resolveDraft($loadedPage);

        $this->mapper->applyPagePayload($payload, $page, $payload->task);

        $persistenceData = $this->coordinator->save($page, $payload->task);

        $this->dispatchEvent(
            new DocumentEvent($page, ['oldPage' => $loadedPage, 'task' => $payload->task]),
            DocumentEvents::PAGE_POST_SAVE_ACTION,
        );

        if (!in_array($payload->task, ['publish', 'unpublish'], true)) {
            $this->elementDraftService->saveDocument($page);
        }

        if ($payload->task === 'publish' || $payload->task === 'unpublish') {
            $data = $persistenceData->data;
            if ($staticGeneratorEnabled = $page->getStaticGeneratorEnabled()) {
                $data['staticGeneratorEnabled'] = $staticGeneratorEnabled;
                $data['staticLastGenerated'] = $this->staticPageGenerator->getLastModified($page);
            }

            return new SavePagePublishedResult(
                data: $data,
                treeData: $persistenceData->treeData
            );
        }

        return new SavePageDraftResult(
            treeData: $persistenceData->treeData,
            draft: $persistenceData->draft ?? []
        );
    }
}
