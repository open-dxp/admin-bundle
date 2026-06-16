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

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Helper\DocumentVersionHelper;
use OpenDxp\Bundle\AdminBundle\Normalizer\ElementResponseNormalizer;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Element\EditLockService;
use OpenDxp\Document\StaticPageGenerator;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element;
use OpenDxp\Model\Schedule\Task;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetPageDataHandler
{
    public function __construct(
        private readonly StaticPageGenerator $staticPageGenerator,
        private readonly EditLockService $editLockService,
        private readonly ElementResponseNormalizer $normalizer,
        private readonly AdminUserContextInterface $userContext,
    ) {}

    public function __invoke(int $id): GetPageDataResult
    {
        $page = Document\Page::getById($id);
        if (!$page) {
            throw new NotFoundHttpException('Page not found');
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

        $this->normalizer->normalize($page, $data, self::class, ['draftVersion' => $draftVersion]);

        return new GetPageDataResult(page: $page, data: $data, draftVersion: $draftVersion);
    }
}
