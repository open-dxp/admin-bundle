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

namespace OpenDxp\Bundle\AdminBundle\Service\Element;

use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Model\Element\Service as ElementService;
use OpenDxp\Model\Version;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Guards the routes that read or change the version history of an element. Listing the
 * versions already requires the `versions` permission of the element; deleting or editing
 * one has to ask for the same permission.
 *
 * @internal
 */
final class VersionPermissionChecker
{
    private const array ALLOWED_TYPES = ['asset', 'document', 'object'];

    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function assertCanManageVersion(Version $version): void
    {
        // Discarding an auto-save is part of editing and does not touch a stored version.
        if ($this->isOwnAutoSave($version)) {
            return;
        }

        $this->assertCanManageElement($version->getCtype(), $version->getCid());
    }

    public function assertCanManageElement(string $elementType, int $elementId): void
    {
        if (!in_array($elementType, self::ALLOWED_TYPES, true)) {
            throw new NotFoundHttpException('Element type not found');
        }

        $element = ElementService::getElementById($elementType, $elementId);
        if (!$element) {
            throw new NotFoundHttpException(sprintf('%s with id [%d] does not exist', $elementType, $elementId));
        }

        if (!$element->isAllowed('versions')) {
            throw new AccessDeniedHttpException(sprintf('Permission denied, %s id [%d]', $elementType, $elementId));
        }
    }

    private function isOwnAutoSave(Version $version): bool
    {
        $userId = $this->userContext->getAdminUser()?->getId();

        return $userId !== null
            && $version->isAutoSave()
            && $version->getUserId() === $userId;
    }
}
