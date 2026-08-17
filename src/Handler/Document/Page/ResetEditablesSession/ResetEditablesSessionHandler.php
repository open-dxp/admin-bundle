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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Page\ResetEditablesSession;

use OpenDxp\Bundle\AdminBundle\Service\Element\ElementDraftService;
use OpenDxp\Model\Document;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ResetEditablesSessionHandler
{
    /**
     * Mirrors TargetingDocumentInterface::TARGET_GROUP_EDITABLE_PREFIX.
     * Hardcoded because the personalization bundle may not be installed
     */
    private const string TARGET_GROUP_EDITABLE_PREFIX = 'persona_-';

    public function __construct(private readonly ElementDraftService $elementDraftService)
    {
    }

    public function __invoke(ResetEditablesSessionPayload $payload): void
    {
        $doc = Document\PageSnippet::getById($payload->id);

        if (!$doc) {
            throw new NotFoundHttpException('Document not found');
        }

        foreach ($doc->getEditables() as $editable) {
            // remove all but target group data
            if (!str_starts_with($editable->getName(), self::TARGET_GROUP_EDITABLE_PREFIX)) {
                $doc->removeEditable($editable->getName());
            }
        }

        $this->elementDraftService->saveDocument($doc, useForSave: true);
    }
}
