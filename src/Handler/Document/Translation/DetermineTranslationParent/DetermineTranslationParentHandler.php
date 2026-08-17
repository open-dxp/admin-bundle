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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\DetermineTranslationParent;

use OpenDxp\Bundle\AdminBundle\Factory\ElementServiceFactory;
use OpenDxp\Model\Document;

final class DetermineTranslationParentHandler
{
    public function __construct(private readonly ElementServiceFactory $serviceFactory)
    {
    }

    public function __invoke(DetermineTranslationParentPayload $payload): DetermineTranslationParentResult
    {
        $document = Document::getById($payload->id);
        if (!$document) {
            return new DetermineTranslationParentResult(false, null, null);
        }

        $service = $this->serviceFactory->createDocumentService();
        $document = $document->getId() === 1 ? $document : $document->getParent();

        $translations = $service->getTranslations($document);
        if (isset($translations[$payload->language])) {
            $targetDocument = Document::getById($translations[$payload->language]);

            return new DetermineTranslationParentResult(
                true,
                $targetDocument?->getRealFullPath(),
                $targetDocument?->getId(),
            );
        }

        return new DetermineTranslationParentResult(false, null, null);
    }
}
