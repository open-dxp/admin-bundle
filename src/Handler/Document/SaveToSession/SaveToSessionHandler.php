<?php

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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\SaveToSession;

use OpenDxp\Bundle\AdminBundle\Service\Document\DocumentPayloadMapper;
use OpenDxp\Bundle\AdminBundle\Service\Element\SessionService;
use OpenDxp\Model\Document;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class SaveToSessionHandler
{
    public function __construct(
        private readonly SessionService $sessionService,
        private readonly DocumentPayloadMapper $mapper,
    ) {
    }

    public function __invoke(SaveToSessionPayload $payload): void
    {
        if (!$payload->id) {
            return;
        }

        $document = $this->sessionService->getOrLoadDocument($payload->id);
        if (!$document) {
            throw new NotFoundHttpException('Document not found in session');
        }

        $document->setInDumpState(true);

        if ($document instanceof Document\Email) {
            $this->mapper->applyPagePayload($payload->email, $document);
        } elseif ($document instanceof Document\PageSnippet) {
            $this->mapper->applyPagePayload($payload->page, $document);
        } elseif ($document instanceof Document\Link) {
            $this->mapper->applyLinkPayload($payload->link, $document);
        } elseif ($document instanceof Document\Hardlink) {
            $this->mapper->applyHardlinkPayload($payload->hardlink, $document);
        } elseif ($document instanceof Document\Folder) {
            $this->mapper->applyFolderPayload($payload->folder, $document);
        }

        $this->sessionService->saveDocument($document);
    }
}
