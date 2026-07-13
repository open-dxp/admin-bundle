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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Snippet\SaveSnippet;

use OpenDxp\Bundle\AdminBundle\Helper\DocumentVersionHelper;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Snippet\SaveSnippet\SaveSnippetPayload;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Document\DocumentPayloadMapper;
use OpenDxp\Bundle\AdminBundle\Service\Document\DocumentPersistenceCoordinator;
use OpenDxp\Bundle\AdminBundle\Service\Element\ElementDraftService;
use OpenDxp\Model\Document\Snippet;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class SaveSnippetHandler
{
    public function __construct(
        private readonly ElementDraftService $elementDraftService,
        private readonly DocumentPayloadMapper $mapper,
        private readonly DocumentPersistenceCoordinator $coordinator,
        private readonly AdminUserContextInterface $userContext,
    ) {}

    public function __invoke(SaveSnippetPayload $payload, bool $sessionAware = true): SaveSnippetPublishedResult|SaveSnippetDraftResult
    {
        $snippet = Snippet::getById($payload->id);
        if (!$snippet) {
            throw new NotFoundHttpException('Snippet not found');
        }

        if ($sessionAware) {
            $sessionSnippet = $this->elementDraftService->getDocument($snippet);
            if ($sessionSnippet instanceof Snippet) {
                $snippet = $sessionSnippet;
            } else {
                $snippet = DocumentVersionHelper::resolveLatestDraft($snippet, userId: $this->userContext->getAdminUser()?->getId());
            }
        }

        $this->mapper->applyPagePayload($payload, $snippet);

        $result = $this->coordinator->save($snippet, $payload->task);

        if ($sessionAware) {
            $this->elementDraftService->saveDocument($result->document);
        }

        $savedSnippet = $result->document instanceof Snippet ? $result->document : $snippet;

        if ($result->task === 'publish' || $result->task === 'unpublish') {
            return new SaveSnippetPublishedResult(
                data: [
                    'versionDate' => $savedSnippet->getModificationDate(),
                    'versionCount' => $savedSnippet->getVersionCount(),
                ],
                treeData: $result->treeData,
            );
        }

        $draft = [];
        if ($result->version) {
            $draft = [
                'id' => $result->version->getId(),
                'modificationDate' => $result->version->getDate(),
                'isAutoSave' => $result->version->isAutoSave(),
            ];
        }

        return new SaveSnippetDraftResult(draft: $draft);
    }
}
