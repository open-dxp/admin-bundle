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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Snippet;

use OpenDxp\Bundle\AdminBundle\Helper\DocumentVersionHelper;
use OpenDxp\Bundle\AdminBundle\Payload\Document\SnippetPayload;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Document\DocumentPayloadMapper;
use OpenDxp\Bundle\AdminBundle\Service\Document\DocumentPersistenceCoordinator;
use OpenDxp\Bundle\AdminBundle\Service\Element\SessionService;
use OpenDxp\Model\Document\Snippet;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class SaveSnippetHandler
{
    public function __construct(
        private readonly SessionService $sessionService,
        private readonly DocumentPayloadMapper $mapper,
        private readonly DocumentPersistenceCoordinator $coordinator,
        private readonly AdminUserContextInterface $userContext,
    ) {}

    public function __invoke(int $id, SnippetPayload $payload, bool $sessionAware = true): SaveSnippetResult
    {
        $snippet = Snippet::getById($id);
        if (!$snippet) {
            throw new NotFoundHttpException('Snippet not found');
        }

        if ($sessionAware) {
            $sessionSnippet = $this->sessionService->getDocument($snippet);
            if ($sessionSnippet instanceof Snippet) {
                $snippet = $sessionSnippet;
            } else {
                $snippet = DocumentVersionHelper::resolveLatestDraft($snippet, userId: $this->userContext->getAdminUser()?->getId());
            }
        }

        $this->mapper->applyPagePayload($payload, $snippet);

        $result = $this->coordinator->save($snippet, $payload->task);

        if ($sessionAware) {
            $this->sessionService->saveDocument($result->document);
        }

        return new SaveSnippetResult(
            snippet: $result->document instanceof Snippet ? $result->document : $snippet,
            task: $result->task,
            version: $result->version,
            treeData: $result->treeData,
        );
    }
}
