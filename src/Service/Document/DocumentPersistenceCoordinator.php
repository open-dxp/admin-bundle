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

namespace OpenDxp\Bundle\AdminBundle\Service\Document;

use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\ElementServiceInterface;
use OpenDxp\Model\Document;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class DocumentPersistenceCoordinator
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly ElementServiceInterface $elementService,
    ) {}

    public function save(Document $document, string $task): DocumentPersistenceData
    {
        $document->setModificationDate(time());
        $document->setUserModification($this->userContext->getAdminUser()->getId());

        $version = null;

        if ($task === 'publish' && $document->isAllowed('publish')) {
            $document->setPublished(true);
            $document->save();
        } elseif ($task === 'unpublish' && $document->isAllowed('unpublish')) {
            $document->setPublished(false);
            $document->save();
        } elseif (in_array($task, ['save', 'version', 'autosave'], true) && $document->isAllowed('save')) {
            if ($document instanceof Document\PageSnippet) {
                if ($task === 'autosave' || $document->isPublished()) {
                    $version = $document->saveVersion(true, true, null, $task === 'autosave');
                } else {
                    $document->save();
                }
            }
        } elseif ($task === 'scheduler' && $document->isAllowed('settings')) {
            if ($document instanceof Document\PageSnippet
                || $document instanceof Document\Hardlink
                || $document instanceof Document\Link) {
                $document->saveScheduledTasks();
            }
        } else {
            throw new AccessDeniedHttpException();
        }

        if ($document instanceof Document\PageSnippet && in_array($task, ['publish', 'version'], true)) {
            $document->deleteAutoSaveVersions($this->userContext->getAdminUser()->getId());
        }

        return new DocumentPersistenceData(
            data: [
                'versionDate'  => $document->getModificationDate(),
                'versionCount' => $document->getVersionCount(),
            ],
            treeData: $this->elementService->getElementTreeNodeConfig($document),
            draft: $version ? [
                'id'               => $version->getId(),
                'modificationDate' => $version->getDate(),
                'isAutoSave'       => $version->isAutoSave(),
            ] : null,
        );
    }
}
