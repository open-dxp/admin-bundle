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
    ) {}

    public function __invoke(SaveSnippetPayload $payload): SaveSnippetPublishedResult|SaveSnippetDraftResult
    {
        $loadedSnippet = Snippet::getById($payload->id);
        if (!$loadedSnippet) {
            throw new NotFoundHttpException('Snippet not found');
        }

        $snippet = $this->elementDraftService->resolveDraft($loadedSnippet);

        $this->mapper->applyPagePayload($payload, $snippet, $payload->task);

        $persistenceData = $this->coordinator->save($snippet, $payload->task);

        $this->elementDraftService->saveDocument($snippet);

        if ($payload->task === 'publish' || $payload->task === 'unpublish') {
            return new SaveSnippetPublishedResult(
                data: $persistenceData->data,
                treeData: $persistenceData->treeData,
            );
        }

        return new SaveSnippetDraftResult(draft: $persistenceData->draft ?? []);
    }
}
