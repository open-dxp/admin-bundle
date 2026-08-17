<?php


declare(strict_types=1);

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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\GetLanguageTree;

use OpenDxp\Bundle\AdminBundle\Factory\ElementServiceFactory;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Element\ElementServiceInterface;
use OpenDxp\Model\Document;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetLanguageTreeHandler
{
    public function __construct(
        private readonly ElementServiceInterface $elementService,
        private readonly ElementServiceFactory $serviceFactory,
        private readonly AdminUserContextInterface $userContext,
    ) {
    }

    public function __invoke(GetLanguageTreePayload $payload): GetLanguageTreeResult
    {
        $document = Document::getById($payload->node);

        if (!$document) {
            throw new NotFoundHttpException('Document not found');
        }

        $nodes = [];
        foreach ($document->getChildren() as $child) {
            $nodes[] = $this->getTranslationTreeNodeConfig($child, $payload->languages);
        }

        return new GetLanguageTreeResult($nodes);
    }

    private function getTranslationTreeNodeConfig(Document $document, array $languages, ?array $translations = null): array
    {
        $service = $this->serviceFactory->createDocumentService();
        $adminUser = $this->userContext->getAdminUser();

        $config = $this->elementService->getElementTreeNodeConfig($document);

        $translations = $translations ?? $service->getTranslations($document);

        foreach ($languages as $language) {
            if ($languageDocumentId = $translations[$language] ?? false) {
                $languageDocument = Document::getById((int) $languageDocumentId);
                $config[$language] = [
                    'text' => $languageDocument->getKey(),
                    'id' => $languageDocument->getId(),
                    'type' => $languageDocument->getType(),
                    'fullPath' => $languageDocument->getFullPath(),
                    'published' => $languageDocument->getPublished(),
                    'itemType' => 'document',
                    'permissions' => $languageDocument->getUserPermissions($adminUser),
                ];
            } elseif (!$document instanceof Document\Folder) {
                $config[$language] = [
                    'text' => '--',
                    'itemType' => 'empty',
                ];
            }
        }

        return $config;
    }
}
