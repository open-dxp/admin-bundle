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

namespace OpenDxp\Bundle\AdminBundle\Service\Element;

use OpenDxp\Bundle\AdminBundle\Helper\DocumentVersionHelper;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Session\SessionIdentityInterface;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Document;
use OpenDxp\Model\Document\Service as DocumentService;

/**
 * Stashes/retrieves unsaved document, object, and asset edits so an editor can navigate away
 * and come back to unpublished changes. Despite the "session id" key, storage is a DB-backed
 * TmpStore keyed by that id as a correlation token, not Symfony session storage — hence
 * SessionIdentityInterface (read-only id), not a SessionGatewayInterface implementation.
 */
final class ElementDraftService
{
    public function __construct(
        private readonly SessionIdentityInterface $sessionIdentity,
        private readonly AdminUserContextInterface $userContext,
    ) {}

    /**
     * Resolves the version of $document that should be loaded into the editor: a pending
     * session draft if one exists for the current user, otherwise the latest saved draft
     * version, otherwise $document itself.
     *
     * @template T of Document\PageSnippet
     *
     * @param T $document
     *
     * @return T
     */
    public function resolveDraft(Document\PageSnippet $document): Document\PageSnippet
    {
        $sessionDocument = $this->getDocument($document);
        if ($sessionDocument !== null && $sessionDocument::class === $document::class) {
            /** @var T $sessionDocument */
            return $sessionDocument;
        }

        return DocumentVersionHelper::resolveLatestDraft($document, userId: $this->userContext->getAdminUser()?->getId());
    }

    public function saveDocument(Document $doc, bool $useForSave = false): void
    {
        $sessionId = $this->sessionId();
        DocumentService::saveElementToSession($doc, $sessionId);
        if ($useForSave) {
            DocumentService::saveElementToSession($doc, $sessionId, '_useForSave');
        }
    }

    public function getDocument(Document $doc): ?Document
    {
        $sessionId = $this->sessionId();
        $sessionDoc = DocumentService::getElementFromSession('document', $doc->getId(), $sessionId);
        if ($sessionDoc && DocumentService::getElementFromSession('document', $doc->getId(), $sessionId, '_useForSave')) {
            DocumentService::removeElementFromSession('document', $doc->getId(), $sessionId, '_useForSave');
        }

        return $sessionDoc ?: null;
    }

    public function getOrLoadDocument(int $id): ?Document
    {
        $sessionId = $this->sessionId();
        $doc = DocumentService::getElementFromSession('document', $id, $sessionId);
        if ($doc instanceof Document) {
            return $doc;
        }

        $doc = Document\PageSnippet::getById($id);
        if (!$doc) {
            return null;
        }

        $latestVersion = $doc->getLatestVersion($this->userContext->getAdminUser()?->getId());
        if ($latestVersion && ($latestDoc = $latestVersion->loadData()) instanceof Document\PageSnippet) {
            return $latestDoc;
        }

        return $doc;
    }

    public function removeDocument(int $docId): void
    {
        DocumentService::removeElementFromSession('document', $docId, $this->sessionId());
    }

    public function saveObject(DataObject\AbstractObject $obj, string $suffix = ''): void
    {
        DataObject\Service::saveElementToSession($obj, $this->sessionId(), $suffix);
    }

    public function getObject(string $type, int $id): ?DataObject\AbstractObject
    {
        return DataObject\Service::getElementFromSession($type, $id, $this->sessionId()) ?: null;
    }

    public function removeObject(string $type, int $id): void
    {
        DataObject\Service::removeElementFromSession($type, $id, $this->sessionId());
    }

    public function saveAsset(Asset $asset): void
    {
        Asset\Service::saveElementToSession($asset, $this->sessionId());
    }

    private function sessionId(): string
    {
        return $this->sessionIdentity->getId();
    }
}
