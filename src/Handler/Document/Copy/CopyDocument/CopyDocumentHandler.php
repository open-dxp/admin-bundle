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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Copy\CopyDocument;

use OpenDxp\Bundle\AdminBundle\Factory\ElementServiceFactory;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Logger;
use OpenDxp\Model\Document;
use OpenDxp\Tool;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CopyDocumentHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly ElementServiceFactory $serviceFactory,
    ) {}

    public function __invoke(CopyDocumentPayload $payload): CopyDocumentResult
    {
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        $source = Document::getById($payload->sourceId);

        if ($payload->sourceParentId !== null && $payload->targetParentId !== null) {
            $sourceParent = Document::getById($payload->sourceParentId) ?? throw new NotFoundHttpException('Source parent not found');
            $resolvedTargetParentId = $payload->sessionParentId ?? $payload->targetParentId;
            $targetParent = Document::getById($resolvedTargetParentId) ?? throw new NotFoundHttpException('Target parent not found');
            $targetPath = preg_replace('@^' . $sourceParent->getRealFullPath() . '@', $targetParent . '/', $source->getRealPath());
            $target = Document::getByPath($targetPath);
        } else {
            $target = Document::getById($payload->targetId);
        }

        if (!$target instanceof Document) {
            throw new NotFoundHttpException('Target document not found');
        }

        if (!$target->isAllowed('create')) {
            Logger::error('could not execute copy/paste because of missing permissions on target [ ' . $payload->targetId . ' ]');
            throw new AccessDeniedHttpException();
        }

        if ($source === null) {
            throw new NotFoundHttpException('Source document not found');
        }

        if ($source instanceof Document\PageSnippet && $latestVersion = $source->getLatestVersion()) {
            $source = $latestVersion->loadData();
            $source->setPublished(false);
        }

        $documentService = $this->serviceFactory->createDocumentService();

        if ($payload->type === 'child') {
            if ($payload->language !== null && !Tool::isValidLanguage($payload->language)) {
                throw new BadRequestHttpException('Invalid language: ' . $payload->language);
            }

            $newDocument = $documentService->copyAsChild($target, $source, $payload->enableInheritance, $payload->resetIndex, $payload->language);

            return new CopyDocumentResult($payload->sourceId, $newDocument);
        }

        if ($payload->type === 'replace') {
            $documentService->copyContents($target, $source);
        }

        return new CopyDocumentResult($payload->sourceId);
    }
}
