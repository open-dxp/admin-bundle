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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\GetLanguageTreeRoot;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Factory\ElementServiceFactory;
use OpenDxp\Bundle\AdminBundle\Service\Element\ElementServiceInterface;
use OpenDxp\Model\Document;
use OpenDxp\Model\User;
use OpenDxp\Tool;

final class GetLanguageTreeRootHandler
{
    public function __construct(
        private readonly ElementServiceInterface $elementService,
        private readonly ElementServiceFactory $serviceFactory,
        private readonly AdminUserContextInterface $userContext,
    ) {}

    public function __invoke(GetLanguageTreeRootPayload $payload): GetLanguageTreeRootResult
    {
        $document = Document::getById($payload->id);

        if (!$document) {
            throw new AdminOperationFailedException();
        }

        $service = $this->serviceFactory->createDocumentService();
        $adminUser = $this->userContext->getAdminUser();

        $locales = Tool::getSupportedLocales();

        $lang = $document->getProperty('language');

        $columns = [
            [
                'xtype' => 'treecolumn',
                'text' => $lang ? $locales[$lang] : '',
                'dataIndex' => 'text',
                'cls' => $lang ? 'x-column-header_' . strtolower($lang) : null,
                'width' => 300,
                'sortable' => false,
            ],
        ];

        $translations = $service->getTranslations($document);

        $combinedTranslations = $translations;

        if ($parentDocument = $document->getParent()) {
            $parentTranslations = $service->getTranslations($parentDocument);
            foreach ($parentTranslations as $language => $languageDocumentId) {
                $combinedTranslations[$language] = $translations[$language] ?? $languageDocumentId;
            }
        }

        foreach ($combinedTranslations as $language => $languageDocumentId) {
            $languageDocument = Document::getById($languageDocumentId);

            if ($languageDocument && $languageDocument->isAllowed('list') && $language != $document->getProperty('language')) {
                $columns[] = [
                    'text' => $locales[$language],
                    'dataIndex' => $language,
                    'cls' => 'x-column-header_' . strtolower($language),
                    'width' => 300,
                    'sortable' => false,
                ];
            }
        }

        $root = $this->getTranslationTreeNodeConfig($document, array_keys($translations), $translations, $adminUser);

        return new GetLanguageTreeRootResult(
            root: $root,
            columns: $columns,
            languages: array_keys($translations),
        );
    }

    private function getTranslationTreeNodeConfig(
        Document $document,
        array $languages,
        ?array $translations,
        ?User $adminUser,
    ): array {
        $service = $this->serviceFactory->createDocumentService();

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
