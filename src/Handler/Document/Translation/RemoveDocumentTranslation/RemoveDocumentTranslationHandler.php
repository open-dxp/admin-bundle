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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\RemoveDocumentTranslation;

use OpenDxp\Bundle\AdminBundle\Factory\ElementServiceFactory;
use OpenDxp\Model\Document;

final class RemoveDocumentTranslationHandler
{
    public function __construct(private readonly ElementServiceFactory $serviceFactory,)
    {
    }

    public function __invoke(RemoveDocumentTranslationPayload $payload): void
    {
        $sourceDocument = Document::getById($payload->sourceId);
        $targetDocument = Document::getById($payload->targetId);

        if (!$sourceDocument || !$targetDocument) {
            return;
        }

        $this->serviceFactory->createDocumentService()->removeTranslationLink($sourceDocument, $targetDocument);
    }
}
