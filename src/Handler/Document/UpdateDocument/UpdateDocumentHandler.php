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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\UpdateDocument;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Exception\Document\DocumentNotFoundException;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Element\ElementServiceInterface;
use OpenDxp\Event\DocumentEvents;
use OpenDxp\Event\Model\DocumentEvent;
use OpenDxp\Logger;
use OpenDxp\Model\Document;
use RuntimeException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class UpdateDocumentHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly ElementServiceInterface $elementService,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(UpdateDocumentPayload $payload): UpdateDocumentResult
    {
        $document = Document::getById($payload->id);
        if (!$document instanceof Document) {
            throw new DocumentNotFoundException($payload->id);
        }

        $oldPath = (string) $document->getDao()->getCurrentFullPath();
        $oldDocument = Document::getById($payload->id, ['force' => true]);

        $adminUser = $this->userContext->getAdminUser();
        $allowUpdate = true;

        // prevent rename/relocate when newer unpublished version exists
        if ($document instanceof Document\PageSnippet) {
            $latestVersion = $document->getLatestVersion();
            if ($latestVersion &&
                $latestVersion->getData()->getModificationDate() != $document->getModificationDate()
            ) {
                throw new AdminOperationFailedException("You can't rename or relocate if there's a newer not published version");
            }
        }

        if ($document->isAllowed('settings')) {
            if (!empty($payload->updateData['parentId'])) {
                $parentDocument = Document::getById((int) $payload->updateData['parentId']);

                if ($document->getParentId() !== $parentDocument->getId()) {
                    if (!$parentDocument->isAllowed('create')) {
                        throw new RuntimeException('Prevented moving document - no create permission on new parent.');
                    }

                    $intendedPath = $parentDocument->getRealPath();
                    $pKey = $parentDocument->getKey();
                    if (!empty($pKey)) {
                        $intendedPath .= $parentDocument->getKey() . '/';
                    }

                    if (Document\Service::pathExists($intendedPath . $document->getKey())) {
                        $allowUpdate = false;
                    }

                    if ($document->isLocked()) {
                        $allowUpdate = false;
                    }
                }
            }

            if ($allowUpdate) {
                $blockedVars = ['id', 'controller', 'action', 'module'];

                if (!$document->isAllowed('rename') && isset($payload->updateData['key'])) {
                    $blockedVars[] = 'key';
                    Logger::debug('prevented renaming document because of missing permissions ');
                }

                foreach ($payload->updateData as $key => $value) {
                    if (!in_array($key, $blockedVars)) {
                        $document->setValue($key, $value);
                    }
                }

                $document->setUserModification($adminUser->getId());
                $document->save();

                if (isset($payload->updateData['index'])) {
                    $this->updateIndexesOfDocumentSiblings($document, (int) $payload->updateData['index']);
                }

                if ($oldPath && $oldPath != $document->getRealFullPath()) {
                    $this->firePostMoveEvent($document, $oldDocument, $oldPath);
                }

                return new UpdateDocumentResult(
                    treeData: $this->elementService->getElementTreeNodeConfig($document),
                );
            }

            $msg = 'prevented moving document, because document with same path+key already exists' .
                ' or the document is locked. ID: ' . $document->getId();
            Logger::debug($msg);

            throw new AdminOperationFailedException($msg);
        }

        if ($document->isAllowed('rename') && isset($payload->updateData['key'])) {
            $document->setKey($payload->updateData['key']);
            $document->setUserModification($adminUser->getId());
            $document->save();

            if ($oldPath && $oldPath != $document->getRealFullPath()) {
                $this->firePostMoveEvent($document, $oldDocument, $oldPath);
            }

            return new UpdateDocumentResult(
                treeData: $this->elementService->getElementTreeNodeConfig($document),
            );
        }

        Logger::debug('Prevented update document, because of missing permissions.');

        throw new AdminOperationFailedException('Prevented update document, because of missing permissions.');
    }

    private function firePostMoveEvent(Document $document, Document $oldDocument, string $oldPath): void
    {
        $arguments = [
            'oldPath' => $oldPath,
            'oldDocument' => $oldDocument,
        ];
        $documentEvent = new DocumentEvent($document, $arguments);
        $this->eventDispatcher->dispatch($documentEvent, DocumentEvents::POST_MOVE_ACTION);
    }

    private function updateIndexesOfDocumentSiblings(Document $document, int $newIndex): void
    {
        $updateLatestVersionIndex = function (Document $document, int $newIndex): void {
            if ($document instanceof Document\PageSnippet && $latestVersion = $document->getLatestVersion()) {
                $document = $latestVersion->loadData();
                $document->setIndex($newIndex);
                $latestVersion->save();
            }
        };

        $document->saveIndex($newIndex);

        $list = new Document\Listing();
        $list->setCondition('parentId = ? AND id != ?', [$document->getParentId(), $document->getId()]);
        $list->setOrderKey('index');
        $list->setOrder('asc');
        $childrenList = $list->load();

        $count = 0;
        foreach ($childrenList as $child) {
            if ($count === $newIndex) {
                $count++;
            }
            $child->saveIndex($count);
            $updateLatestVersionIndex($child, $count);
            $count++;
        }
    }
}
