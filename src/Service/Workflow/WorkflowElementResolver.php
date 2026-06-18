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

namespace OpenDxp\Bundle\AdminBundle\Service\Workflow;

use Exception;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\Concrete as ConcreteObject;
use OpenDxp\Model\Document;

final class WorkflowElementResolver
{
    public function __construct(private readonly AdminUserContextInterface $userContext) {}

    public function resolve(string $ctype, int $cid): ConcreteObject|Document|Asset
    {
        $element = match ($ctype) {
            'document' => Document::getById($cid),
            'asset' => Asset::getById($cid),
            'object' => ConcreteObject::getById($cid),
            default => null,
        };

        if ($element === null) {
            throw new Exception('Cannot load element ' . $cid . ' of type \'' . $ctype . '\'');
        }

        $element = $this->getLatestVersion($element);
        $element->setUserModification((int) $this->userContext->getAdminUser()->getId());

        return $element;
    }

    private function getLatestVersion(ConcreteObject|Document|Asset $element): ConcreteObject|Document|Asset
    {
        if (
            $element instanceof Document\Folder
            || $element instanceof Asset\Folder
            || $element instanceof Document\Hardlink
            || $element instanceof Document\Link
        ) {
            return $element;
        }

        if ($element instanceof Document\PageSnippet) {
            $latestVersion = $element->getLatestVersion();
            if ($latestVersion) {
                $latestDoc = $latestVersion->loadData();
                if ($latestDoc instanceof Document\PageSnippet) {
                    $element = $latestDoc;
                }
            }
        }

        if ($element instanceof ConcreteObject) {
            $latestVersion = $element->getLatestVersion();
            if ($latestVersion) {
                $latestObj = $latestVersion->loadData();
                if ($latestObj instanceof ConcreteObject) {
                    $element = $latestObj;
                }
            }
        }

        return $element;
    }
}
