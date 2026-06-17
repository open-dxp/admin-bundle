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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\AddDocument;

use Exception;
use OpenDxp\Bundle\AdminBundle\Factory\ElementServiceFactory;
use OpenDxp\Logger;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\Service;
use OpenDxp\Model\User;
use OpenDxp\Resolver\ResolverInterface;
use OpenDxp\Tool;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class AddDocumentHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly ElementServiceFactory $serviceFactory,
        private readonly ResolverInterface $documentClassResolver,
        private readonly string $defaultDocumentController,
    ) {}

    public function __invoke(AddDocumentPayload $payload): AddDocumentResult {
        $adminUser = $this->userContext->getAdminUser();
        $parentDocument = Document::getById($payload->parentId);

        if (!$parentDocument || !$parentDocument->isAllowed('create')) {
            throw new AccessDeniedHttpException('Prevented adding a document because of missing permissions');
        }

        $intendedPath = $parentDocument->getRealFullPath() . '/' . $payload->key;

        if (Document\Service::pathExists($intendedPath)) {
            throw new BadRequestHttpException(
                sprintf('Prevented adding a document because document with same path+key [%s] already exists', $intendedPath)
            );
        }

        $createValues = [
            'userOwner' => $adminUser->getId(),
            'userModification' => $adminUser->getId(),
            'published' => false,
        ];

        $createValues['key'] = Service::getValidKey($payload->key, 'document');

        // determine template / controller from docType or translationsBaseDocument
        $docType = Document\DocType::getById($payload->docTypeId ?? '');

        if ($docType) {
            $createValues['template'] = $docType->getTemplate();
            $createValues['controller'] = $docType->getController();
            $createValues['staticGeneratorEnabled'] = $docType->getStaticGeneratorEnabled();
        } elseif ($payload->translationsBaseDocumentId !== null) {
            $translationsBaseDocument = Document::getById((int) $payload->translationsBaseDocumentId);
            if ($translationsBaseDocument instanceof Document\PageSnippet) {
                $createValues['template'] = $translationsBaseDocument->getTemplate();
                $createValues['controller'] = $translationsBaseDocument->getController();
            }
        } elseif (in_array($payload->type, ['page', 'snippet', 'email'])) {
            $createValues['controller'] = $this->defaultDocumentController;
        }

        if ($payload->inheritanceSource !== null) {
            $createValues['contentMainDocumentId'] = $payload->inheritanceSource;
        }

        $document = match ($payload->type) {
            'page' => $this->createPage($parentDocument, $createValues, $payload->title, $payload->name),
            'snippet' => Document\Snippet::create($parentDocument->getId(), $createValues),
            'email' => Document\Email::create($parentDocument->getId(), $createValues),
            'link' => Document\Link::create($parentDocument->getId(), $createValues),
            'hardlink' => Document\Hardlink::create($parentDocument->getId(), $createValues),
            'folder' => $this->createFolder($parentDocument, $createValues),
            default => $this->createCustomType($payload->type, $parentDocument, $createValues),
        };

        // link translation if translationsBaseDocument given
        if ($payload->translationsBaseDocumentId !== null) {
            $translationsBaseDocument = Document::getById((int) $payload->translationsBaseDocumentId);
            if ($translationsBaseDocument) {
                $properties = $translationsBaseDocument->getProperties();
                $properties = [...$properties, ...$document->getProperties()];
                $document->setProperties($properties);
                $document->setProperty('language', 'text', $payload->language, false, true);
                $document->save();

                $service = $this->serviceFactory->createDocumentService();
                $service->addTranslation($translationsBaseDocument, $document);
            }
        }

        return new AddDocumentResult($document);
    }

    private function createPage(Document $parentDocument, array $createValues, ?string $title, ?string $name): Document\Page
    {
        $document = Document\Page::create($parentDocument->getId(), $createValues, false);
        $document->setTitle((string) $title);
        $document->setProperty('navigation_name', 'text', $name, false, false);
        $document->save();

        return $document;
    }

    private function createFolder(Document $parentDocument, array $createValues): Document\Folder
    {
        $document = Document\Folder::create($parentDocument->getId(), $createValues);
        $document->setPublished(true);
        $document->save();

        return $document;
    }

    private function createCustomType(string $type, Document $parentDocument, array $createValues): Document
    {
        $classname = $this->documentClassResolver->resolve($type);

        if ($classname !== null && Tool::classExists($classname)) {
            $document = $classname::create($parentDocument->getId(), $createValues);
            $document->save();

            return $document;
        }

        Logger::debug("Unknown document type, can't add [ $type ] ");

        throw new BadRequestHttpException(sprintf("Unknown document type '%s'", $type));
    }
}
