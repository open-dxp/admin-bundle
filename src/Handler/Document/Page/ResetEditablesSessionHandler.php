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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Page;

use OpenDxp\Bundle\AdminBundle\Service\Element\SessionService;
use OpenDxp\Model\Document;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ResetEditablesSessionHandler
{
    public function __construct(private readonly SessionService $sessionService) {}

    public function __invoke(int $docId): void
    {
        $doc = Document\PageSnippet::getById($docId);

        if (!$doc) {
            throw new NotFoundHttpException('Document not found');
        }

        foreach ($doc->getEditables() as $editable) {
            // remove all but target group data
            // Hardcoded the TARGET_GROUP_EDITABLE_PREFIX prefix here as we shouldn't remove the bundle specific editables even if bundle is not enabled/installed
            if (!preg_match('/^' . preg_quote('persona_ -', '/') . '/', $editable->getName())) {
                $doc->removeEditable($editable->getName());
            }
        }

        $this->sessionService->saveDocument($doc, useForSave: true);
    }
}
