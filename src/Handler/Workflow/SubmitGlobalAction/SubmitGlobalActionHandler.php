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

namespace OpenDxp\Bundle\AdminBundle\Handler\Workflow\SubmitGlobalAction;

use Exception;
use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Resolver\Workflow\WorkflowElementResolver;
use OpenDxp\Model\Element\ValidationException;
use OpenDxp\Workflow\Manager;
use Symfony\Component\Workflow\Registry;

final class SubmitGlobalActionHandler
{
    public function __construct(
        private readonly Manager $workflowManager,
        private readonly Registry $workflowRegistry,
        private readonly WorkflowElementResolver $elementResolver,
    ) {
    }

    public function __invoke(SubmitGlobalActionPayload $payload): SubmitGlobalActionResult
    {
        $element = $this->elementResolver->resolve($payload->ctype, $payload->cid);

        $workflow = $this->workflowRegistry->get($element, $payload->workflowName);

        $globalAction = $this->workflowManager->getGlobalAction($payload->workflowName, $payload->transition);
        $saveSubject = !$globalAction || $globalAction->getSaveSubject();

        try {
            $this->workflowManager->applyGlobalAction($workflow, $element, $payload->transition, $payload->workflowOptions, $saveSubject);
        } catch (ValidationException $e) {
            $reason = '';
            if (count($e->getSubItems()) > 0) {
                $reason = '<ul>' . implode('', array_map(static fn ($item) => '<li>' . $item . '</li>', $e->getSubItems())) . '</ul>';
            }

            throw new AdminOperationFailedException($e->getMessage(), ['reasons' => [$reason]]);
        } catch (Exception $e) {
            throw new AdminOperationFailedException('error performing action on this element', ['reasons' => [$e->getMessage()]]);
        }

        return new SubmitGlobalActionResult();
    }
}
