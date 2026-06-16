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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Translation;

use Exception;
use OpenDxp\Bundle\AdminBundle\Factory\ElementServiceFactory;
use OpenDxp\Model\Document;

final class AddDocumentTranslationHandler
{
    public function __construct(
        private readonly ElementServiceFactory $serviceFactory,
    ) {}

    public function __invoke(int $sourceId, string $targetPath): void
    {
        $sourceDocument = Document::getById($sourceId);
        $targetDocument = Document::getByPath($targetPath);

        if (!$sourceDocument || !$targetDocument) {
            return;
        }

        if (empty($sourceDocument->getProperty('language'))) {
            throw new Exception(sprintf('Source Document(ID:%s) Language(Properties) missing', $sourceDocument->getId()));
        }

        if (empty($targetDocument->getProperty('language'))) {
            throw new Exception(sprintf('Target Document(ID:%s) Language(Properties) missing', $targetDocument->getId()));
        }

        $service = $this->serviceFactory->createDocumentService();
        if ($service->getTranslationSourceId($targetDocument) != $targetDocument->getId()) {
            throw new Exception('Target Document already linked to Source Document ID(' . $service->getTranslationSourceId($targetDocument) . '). Please unlink existing relation first.');
        }

        $service->addTranslation($sourceDocument, $targetDocument);
    }
}
