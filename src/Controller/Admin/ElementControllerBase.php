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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin;

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetDeleteInfo\GetDeleteInfoHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetDeleteInfo\GetDeleteInfoPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetTreeRoot\GetTreeRootHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetTreeRoot\GetTreeRootPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
abstract class ElementControllerBase extends AdminAbstractController
{
    #[Route('/tree-get-root', name: 'treegetroot', methods: ['GET'])]
    public function treeGetRootAction(
        GetTreeRootPayload $payload,
        GetTreeRootHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload), rootProperty: 'treeNodeConfig');
    }

    #[Route('/delete-info', name: 'deleteinfo', methods: ['GET'])]
    public function deleteInfoAction(
        GetDeleteInfoHandler $handler,
        GetDeleteInfoPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload), envelope: false);
    }
}
