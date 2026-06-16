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
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\GetPageDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Snippet\GetSnippetDataHandler;
use OpenDxp\Bundle\AdminBundle\Normalizer\ElementResponseNormalizerInterface;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Version;

final class DraftNormalizer implements ElementResponseNormalizerInterface
{
    public function supports(ElementInterface $element, string $handlerClass): bool
    {
        return $element instanceof Document\PageSnippet
            && in_array($handlerClass, [GetPageDataHandler::class, GetEmailDataHandler::class, GetSnippetDataHandler::class], true);
    }

    public function normalize(ElementInterface $element, array &$data, array $context = []): void
    {
        $draftVersion = $context['draftVersion'] ?? null;
        if (!$draftVersion instanceof Version) {
            return;
        }

        $fresh = Document::getById($element->getId(), ['force' => true]);
        if ($fresh->getModificationDate() < $draftVersion->getDate()) {
            $data['draft'] = [
                'id' => $draftVersion->getId(),
                'modificationDate' => $draftVersion->getDate(),
                'isAutoSave' => $draftVersion->isAutoSave(),
            ];
        }
    }
}
