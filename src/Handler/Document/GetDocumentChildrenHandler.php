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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document;

use OpenDxp\Bundle\AdminBundle\Service\ElementServiceInterface;
use OpenDxp\Model\Document;

final class GetDocumentChildrenHandler
{
    public function __construct(
        private readonly ElementServiceInterface $elementService,
    ) {}

    public function __invoke(Document\Listing $list): GetDocumentChildrenResult
    {
        $childrenList = $list->load();

        $documents = [];
        foreach ($childrenList as $childDocument) {
            $documentTreeNode = $this->elementService->getElementTreeNodeConfig($childDocument);
            // the !isset is for printContainer case, there are no permissions set there
            if (!isset($documentTreeNode['permissions']['list']) || $documentTreeNode['permissions']['list'] == 1) {
                $documents[] = $documentTreeNode;
            }
        }

        return new GetDocumentChildrenResult($documents);
    }
}
