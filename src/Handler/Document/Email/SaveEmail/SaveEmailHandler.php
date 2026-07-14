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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Email\SaveEmail;

use OpenDxp\Bundle\AdminBundle\Service\Document\DocumentPayloadMapper;
use OpenDxp\Bundle\AdminBundle\Service\Document\DocumentPersistenceCoordinator;
use OpenDxp\Bundle\AdminBundle\Service\Element\ElementDraftService;
use OpenDxp\Model\Document\Email;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class SaveEmailHandler
{
    public function __construct(
        private readonly ElementDraftService $elementDraftService,
        private readonly DocumentPayloadMapper $mapper,
        private readonly DocumentPersistenceCoordinator $coordinator,
    ) {}

    public function __invoke(SaveEmailPayload $payload): SaveEmailPublishedResult|SaveEmailDraftResult
    {
        $loadedEmail = Email::getById($payload->id);
        if (!$loadedEmail) {
            throw new NotFoundHttpException('Email not found');
        }

        $email = $this->elementDraftService->resolveDraft($loadedEmail);

        $this->mapper->applyPagePayload($payload, $email);

        $persistenceData = $this->coordinator->save($email, $payload->task);

        $this->elementDraftService->saveDocument($email);

        if ($payload->task === 'publish' || $payload->task === 'unpublish') {
            return new SaveEmailPublishedResult(
                data: $persistenceData->data,
                treeData: $persistenceData->treeData,
            );
        }

        return new SaveEmailDraftResult(draft: $persistenceData->draft ?? []);
    }
}
