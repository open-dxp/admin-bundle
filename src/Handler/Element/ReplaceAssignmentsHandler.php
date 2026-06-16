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

namespace OpenDxp\Bundle\AdminBundle\Handler\Element;

use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element;
use OpenDxp\Model\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class ReplaceAssignmentsHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(
        string $type,
        int $id,
        string $sourceType,
        int $sourceId,
        string $targetType,
        int $targetId,
    ): void {
        $adminUser = $this->userContext->getAdminUser();
        $element = Element\Service::getElementById($type, $id);
        $sourceEl = Element\Service::getElementById($sourceType, $sourceId);
        $targetEl = Element\Service::getElementById($targetType, $targetId);

        if (!$element || !$sourceEl || !$targetEl) {
            throw new NotFoundHttpException('One or more elements not found');
        }

        if ($sourceType !== $targetType || $sourceEl->getType() !== $targetEl->getType()) {
            throw new BadRequestHttpException('source-type and target-type do not match');
        }

        if (!$element->isAllowed('save')) {
            throw new AccessDeniedHttpException();
        }

        $rewriteConfig = [
            $sourceType => [
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
