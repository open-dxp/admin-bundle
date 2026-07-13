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

use OpenDxp\Bundle\AdminBundle\Helper\DocumentVersionHelper;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Email\SaveEmail\SaveEmailPayload;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Document\DocumentPayloadMapper;
use OpenDxp\Bundle\AdminBundle\Service\Document\DocumentPersistenceCoordinator;
use OpenDxp\Bundle\AdminBundle\Service\Element\SessionService;
use OpenDxp\Model\Document\Email;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class SaveEmailHandler
{
    public function __construct(
        private readonly SessionService $sessionService,
        private readonly DocumentPayloadMapper $mapper,
        private readonly DocumentPersistenceCoordinator $coordinator,
        private readonly AdminUserContextInterface $userContext,
    ) {}

    public function __invoke(SaveEmailPayload $payload, bool $sessionAware = true): SaveEmailPublishedResult|SaveEmailDraftResult
    {
        $email = Email::getById($payload->id);
        if (!$email) {
            throw new NotFoundHttpException('Email not found');
        }

        if ($sessionAware) {
            $sessionEmail = $this->sessionService->getDocument($email);
            if ($sessionEmail instanceof Email) {
                $email = $sessionEmail;
            } else {
                $email = DocumentVersionHelper::resolveLatestDraft($email, userId: $this->userContext->getAdminUser()?->getId());
            }
        }

        $this->mapper->applyPagePayload($payload, $email);

        $result = $this->coordinator->save($email, $payload->task);

        if ($sessionAware) {
            $this->sessionService->saveDocument($result->document);
        }

        $savedEmail = $result->document instanceof Email ? $result->document : $email;

        if ($result->task === 'publish' || $result->task === 'unpublish') {
            return new SaveEmailPublishedResult(
                data: [
                    'versionDate' => $savedEmail->getModificationDate(),
                    'versionCount' => $savedEmail->getVersionCount(),
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

        return new SaveEmailDraftResult(draft: $draft);
    }
}
