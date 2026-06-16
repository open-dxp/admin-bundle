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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Page;

use OpenDxp\Bundle\AdminBundle\Helper\DocumentVersionHelper;
use OpenDxp\Bundle\AdminBundle\Payload\Document\PagePayload;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Document\DocumentPayloadMapper;
use OpenDxp\Bundle\AdminBundle\Service\Document\DocumentPersistenceCoordinator;
use OpenDxp\Bundle\AdminBundle\Service\Element\SessionService;
use OpenDxp\Event\DocumentEvents;
use OpenDxp\Event\Model\DocumentEvent;
use OpenDxp\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use OpenDxp\Model\Document\Page;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class SavePageHandler
{
    use RecursionBlockingEventDispatchHelperTrait;

    public function __construct(
        private readonly SessionService $sessionService,
        private readonly DocumentPayloadMapper $mapper,
        private readonly DocumentPersistenceCoordinator $coordinator,
        private readonly AdminUserContextInterface $userContext,
    ) {}

    public function __invoke(int $id, PagePayload $payload, bool $sessionAware = true): SavePageResult
    {
        $oldPage = Page::getById($id);
        if (!$oldPage) {
            throw new NotFoundHttpException('Page not found');
        }

        if ($sessionAware) {
            $sessionPage = $this->sessionService->getDocument($oldPage);
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
            $this->sessionService->saveDocument($result->document);
        }

        return new SavePageResult(
            page: $result->document instanceof Page ? $result->document : $page,
            oldPage: $oldPage,
            task: $result->task,
            version: $result->version,
            treeData: $result->treeData,
        );
    }
}
