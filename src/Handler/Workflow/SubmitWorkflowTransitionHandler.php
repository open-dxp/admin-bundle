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

namespace OpenDxp\Bundle\AdminBundle\Handler\Workflow;

use OpenDxp\Bundle\AdminBundle\Service\Workflow\WorkflowElementResolver;
use OpenDxp\Model\Element\ValidationException;
use OpenDxp\Workflow\Manager;
use Symfony\Component\Workflow\Registry;

final class SubmitWorkflowTransitionHandler
{
    public function __construct(
        private readonly Manager $workflowManager,
        private readonly Registry $workflowRegistry,
        private readonly WorkflowElementResolver $elementResolver,
    ) {}

    /**
     * @throws ValidationException
     * @throws \RuntimeException
     */
    public function __invoke(
        string $ctype,
        int $cid,
        string $workflowName,
        string $transition,
        array $workflowOptions,
    ): SubmitWorkflowTransitionResult {
        $element = $this->elementResolver->resolve($ctype, $cid);

        $workflow = $this->workflowRegistry->get($element, $workflowName);

        if (!$workflow->can($element, $transition)) {
            $blockTransitionList = $workflow->buildTransitionBlockerList($element, $transition);
            $reasons = array_map(
                static fn ($item) => $item->getMessage(),
                iterator_to_array($blockTransitionList->getIterator(), true)
            );

            return new SubmitWorkflowTransitionResult(blocked: true, blockerReasons: $reasons);
        }

        $this->workflowManager->applyWithAdditionalData($workflow, $element, $transition, $workflowOptions, true);

        return new SubmitWorkflowTransitionResult(blocked: false, blockerReasons: []);
    }
}
