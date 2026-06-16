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

use OpenDxp\Bundle\AdminBundle\Factory\ElementServiceFactory;
use OpenDxp\Model\Document;

final class CheckTranslationLanguageHandler
{
    public function __construct(
        private readonly ElementServiceFactory $serviceFactory,
    ) {}

    public function __invoke(?string $path): CheckTranslationLanguageResult
    {
        $document = Document::getByPath($path);
        if (!$document) {
            return new CheckTranslationLanguageResult(false, null, null);
        }

        $language = $document->getProperty('language');
        $found = !empty($language);
        $translationLinks = array_keys($this->serviceFactory->createDocumentService()->getTranslations($document));

        return new CheckTranslationLanguageResult($found, $language ?: null, $translationLinks);
    }
}
