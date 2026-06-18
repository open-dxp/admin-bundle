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
use OpenDxp\Bundle\AdminBundle\Service\ElementServiceInterface;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

/**
 * @internal
 */
abstract class ElementControllerBase extends AdminAbstractController
{
    public function __construct(protected ElementServiceInterface $elementService)
    {
    }

    /**
     * @return array<string, mixed>
     */
    protected function getTreeNodeConfig(ElementInterface $element): array
    {
        return [];
    }

    #[Route('/tree-get-root', name: 'treegetroot', methods: ['GET'])]
    public function treeGetRootAction(
        #[MapQueryParameter] ?string $elementType = null,
        #[MapQueryParameter(flags: FILTER_NULL_ON_FAILURE)] ?int $id = null,
    ): JsonResponse
    {
        $type = $elementType;
        $allowedTypes = ['asset', 'document', 'object'];

        $id = $id ?? 1;

        if (in_array($type, $allowedTypes)) {
            $root = Service::getElementById($type, $id);
            if ($root?->isAllowed('list')) {
                return $this->adminJson($this->getTreeNodeConfig($root));
            }

            return $this->adminJson(ApiResponse::error(null, ['id' => $id]));
        }

        return $this->adminJson(ApiResponse::error('missing_permission'));
    }

    #[Route('/delete-info', name: 'deleteinfo', methods: ['GET'])]
    public function deleteInfoAction(
        GetDeleteInfoHandler $handler,
        GetDeleteInfoPayload $payload,
    ): JsonResponse
    {
        return $this->adminJson($handler($payload));
    }
}
