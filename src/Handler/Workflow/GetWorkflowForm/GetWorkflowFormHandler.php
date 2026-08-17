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

namespace OpenDxp\Bundle\AdminBundle\Handler\Workflow\GetWorkflowForm;

use OpenDxp\Bundle\AdminBundle\Resolver\Workflow\WorkflowElementResolver;
use OpenDxp\Workflow\Manager;
use OpenDxp\Workflow\Transition;

final class GetWorkflowFormHandler
{
    public function __construct(
        private readonly Manager $workflowManager,
        private readonly WorkflowElementResolver $elementResolver,
    ) {}

    public function __invoke(GetWorkflowFormPayload $payload): GetWorkflowFormResult
    {
        $element = $this->elementResolver->resolve($payload->ctype, $payload->cid);

        try {
            $workflow = $this->workflowManager->getWorkflowIfExists($element, $payload->workflowName);

            if (empty($workflow)) {
                return new GetWorkflowFormResult(
                    message: 'workflow not found',
                    notes_enabled: false,
                    notes_required: false,
                    additional_fields: [],
                );
            }

            $enabledTransitions = $workflow->getEnabledTransitions($element);
            $transition = null;
            foreach ($enabledTransitions as $_transition) {
                if ($_transition->getName() === $payload->transitionName) {
                    $transition = $_transition;
                }
            }

            if (!$transition instanceof Transition) {
                return new GetWorkflowFormResult(
                    message: sprintf('transition %s currently not allowed', $payload->transitionName),
                    notes_enabled: false,
                    notes_required: false,
                    additional_fields: [],
                );
            }

            return new GetWorkflowFormResult(
                message: '',
                notes_enabled: false,
                notes_required: $transition->getNotesCommentRequired(),
                additional_fields: [],
            );
        } catch (\Exception $e) {
            return new GetWorkflowFormResult(
                message: $e->getMessage(),
                notes_enabled: false,
                notes_required: false,
                additional_fields: [],
            );
        }
    }
}
