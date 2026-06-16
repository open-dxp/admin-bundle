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

use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Document;
use OpenDxp\Model\Document\Service as DocumentService;
use Symfony\Component\HttpFoundation\RequestStack;

final class SessionService
{
    public function __construct(private readonly RequestStack $requestStack) {}

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

    public function getOrLoadDocument(int $id): ?Document\PageSnippet
    {
        $sessionId = $this->sessionId();
        $doc = DocumentService::getElementFromSession('document', $id, $sessionId);
        if ($doc) {
            return $doc;
        }

        $doc = Document\PageSnippet::getById($id);
        if (!$doc) {
            return null;
        }

        $latestVersion = $doc->getLatestVersion();
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
        return $this->requestStack->getSession()->getId();
    }
}
