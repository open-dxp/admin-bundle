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

namespace OpenDxp\Bundle\AdminBundle\Normalizer\Document;

use OpenDxp\Bundle\AdminBundle\Handler\Document\Email\GetEmailDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Folder\GetFolderDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\GetDocumentDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Hardlink\GetHardlinkDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Link\GetLinkDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\GetPageDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Snippet\GetSnippetDataHandler;
use OpenDxp\Bundle\AdminBundle\Normalizer\ElementResponseNormalizerInterface;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\ElementInterface;

final class TranslationNormalizer implements ElementResponseNormalizerInterface
{
    public function supports(ElementInterface $element, string $handlerClass): bool
    {
        return $element instanceof Document && in_array($handlerClass, [
            GetDocumentDataHandler::class,
            GetEmailDataHandler::class,
            GetFolderDataHandler::class,
            GetHardlinkDataHandler::class,
            GetLinkDataHandler::class,
            GetPageDataHandler::class,
            GetSnippetDataHandler::class,
        ], true);
    }

    public function normalize(ElementInterface $element, array &$data, array $context = []): void
    {
        $service = new Document\Service();
        $translations = $service->getTranslations($element);
        $unlinkTranslations = $service->getTranslations($element, 'unlink');
        $language = $element->getProperty('language');
        unset($translations[$language], $unlinkTranslations[$language]);
        $data['translations'] = $translations;
        $data['unlinkTranslations'] = $unlinkTranslations;
    }
}
