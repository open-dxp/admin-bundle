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

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\ReplaceAssignments;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element;

final class ReplaceAssignmentsHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(ReplaceAssignmentsPayload $payload): void
    {
        $adminUser = $this->userContext->getAdminUser();
        $element = Element\Service::getElementById($payload->type, $payload->id);
        $sourceEl = Element\Service::getElementById($payload->sourceType, $payload->sourceId);
        $targetEl = Element\Service::getElementById($payload->targetType, $payload->targetId);

        if (!$element || !$sourceEl || !$targetEl) {
            throw new AdminOperationFailedException('One or more elements not found');
        }

        if ($payload->sourceType !== $payload->targetType || $sourceEl->getType() !== $targetEl->getType()) {
            throw new AdminOperationFailedException('source-type and target-type do not match');
        }

        if (!$element->isAllowed('save')) {
            throw new AdminOperationFailedException('');
        }

        $rewriteConfig = [
            $payload->sourceType => [
                $sourceEl->getId() => $targetEl->getId(),
            ],
        ];

        if ($element instanceof Document) {
            $element = Document\Service::rewriteIds($element, $rewriteConfig);
        } elseif ($element instanceof DataObject\AbstractObject) {
            $element = DataObject\Service::rewriteIds($element, $rewriteConfig);
        } elseif ($element instanceof Asset) {
            $element = Asset\Service::rewriteIds($element, $rewriteConfig);
        }

        $element->setUserModification($adminUser->getId());
        $element->save();
    }
}
