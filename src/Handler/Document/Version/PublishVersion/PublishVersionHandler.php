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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Version\PublishVersion;

use OpenDxp\Bundle\AdminBundle\Enricher\Element\AdminStyleEnricher;
use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Exception\Document\DocumentNotFoundException;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdBodyPayload;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Element\ElementDraftService;
use OpenDxp\Model\Document;
use OpenDxp\Model\Version;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class PublishVersionHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly ElementDraftService $elementDraftService,
        private readonly AdminStyleEnricher $adminStyleEnricher,
    ) {}

    public function __invoke(IdBodyPayload $payload): PublishVersionResult
    {
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        $version = Version::getById($payload->id);
        $document = $version?->loadData();

        if (!$document instanceof Document) {
            throw new DocumentNotFoundException($payload->id);
        }

        $this->elementDraftService->saveDocument($document);

        $currentDocument = Document::getById($document->getId());
        if (!$currentDocument?->isAllowed('publish')) {
            throw new AccessDeniedHttpException('Missing permission to publish document version');
        }

        $document->setPublished(true);

        try {
            $document->setKey($currentDocument->getKey());
            $document->setPath($currentDocument->getRealPath());
            $document->setUserModification($userId);
            $document->save();
        } catch (\Exception $e) {
            throw new AdminOperationFailedException($e->getMessage());
        }

        $treeData = [];
        $this->adminStyleEnricher->forEditor($document, $treeData);

        return new PublishVersionResult(treeData: $treeData);
    }
}
