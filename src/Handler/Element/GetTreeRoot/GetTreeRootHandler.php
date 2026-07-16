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

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetTreeRoot;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Service\Element\ElementServiceInterface;
use OpenDxp\Model\Element\Service;

final class GetTreeRootHandler
{
    private const array ALLOWED_TYPES = ['asset', 'document', 'object'];

    public function __construct(
        private readonly ElementServiceInterface $elementService,
    ) {}

    public function __invoke(GetTreeRootPayload $payload): GetTreeRootResult
    {
        if (!in_array($payload->elementType, self::ALLOWED_TYPES)) {
            throw new AdminOperationFailedException('missing_permission');
        }

        $root = Service::getElementById($payload->elementType, $payload->id);

        if (!$root?->isAllowed('list')) {
            throw new AdminOperationFailedException('', ['id' => $payload->id]);
        }

        return new GetTreeRootResult($this->elementService->getElementTreeNodeConfig($root));
    }
}
