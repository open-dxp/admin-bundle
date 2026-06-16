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

use OpenDxp\Model;
use OpenDxp\Model\Element;

final class AnalyzePermissionsHandler
{
    public function __invoke(?string $elementType, int $elementId, ?int $userId = null): AnalyzePermissionsResult
    {
        if ($userId) {
            $userList = [];
            if ($user = Model\User::getById($userId)) {
                $userList[] = $user;
            }
        } else {
            $userList = new Model\User\Listing();
            $userList->setCondition('`type` = ?', ['user']);
            $userList = $userList->load();
        }

        $element = Element\Service::getElementById($elementType, $elementId);
        $result = Element\PermissionChecker::check($element, $userList);

        return new AnalyzePermissionsResult(data: $result);
    }
}
