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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Version;

use OpenDxp\Bundle\AdminBundle\Exception\Document\DocumentNotFoundException;
use OpenDxp\Bundle\AdminBundle\Normalizer\ElementResponseNormalizer;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Element\SessionService;
use OpenDxp\Model\Document;
use OpenDxp\Model\Version;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class PublishVersionHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly SessionService $sessionService,
        private readonly ElementResponseNormalizer $normalizer,
    ) {}

    public function __invoke(int $versionId): PublishVersionResult
    {
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        $version = Version::getById($versionId);
        $document = $version?->loadData();

        if (!$document instanceof Document) {
            throw new DocumentNotFoundException($versionId);
        }

        $this->sessionService->saveDocument($document);

        $currentDocument = Document::getById($document->getId());
        if (!$currentDocument?->isAllowed('publish')) {
            throw new AccessDeniedHttpException('Missing permission to publish document version');
        }

        $document->setPublished(true);
        $document->setKey($currentDocument->getKey());
        $document->setPath($currentDocument->getRealPath());
        $document->setUserModification($userId);
        $document->save();

        $treeData = [];
        $this->normalizer->normalize($document, $treeData, self::class);

        return new PublishVersionResult(treeData: $treeData);
    }
}
