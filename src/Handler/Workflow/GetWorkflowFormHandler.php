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
use OpenDxp\Workflow\Manager;
use OpenDxp\Workflow\Transition;

final class GetWorkflowFormHandler
{
    public function __construct(
        private readonly Manager $workflowManager,
        private readonly WorkflowElementResolver $elementResolver,
    ) {}

    public function __invoke(
        string $ctype,
        int $cid,
        string $workflowName,
        string $transitionName,
    ): GetWorkflowFormResult {
        $element = $this->elementResolver->resolve($ctype, $cid);

        $workflow = $this->workflowManager->getWorkflowIfExists($element, $workflowName);

        if (empty($workflow)) {
            return new GetWorkflowFormResult(
                message: 'workflow not found',
                notesEnabled: false,
                notesRequired: false,
                additionalFields: [],
            );
        }

        $enabledTransitions = $workflow->getEnabledTransitions($element);
        $transition = null;
        foreach ($enabledTransitions as $_transition) {
            if ($_transition->getName() === $transitionName) {
                $transition = $_transition;
            }
        }

        if (!$transition instanceof Transition) {
            return new GetWorkflowFormResult(
                message: sprintf('transition %s currently not allowed', $transitionName),
                notesEnabled: false,
                notesRequired: false,
                additionalFields: [],
            );
        }

        return new GetWorkflowFormResult(
            message: '',
            notesEnabled: false,
            notesRequired: $transition->getNotesCommentRequired(),
            additionalFields: [],
        );
    }
}
