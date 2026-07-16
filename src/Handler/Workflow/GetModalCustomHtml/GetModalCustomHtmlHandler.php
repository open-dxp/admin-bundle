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

namespace OpenDxp\Bundle\AdminBundle\Handler\Workflow\GetModalCustomHtml;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Resolver\Workflow\WorkflowElementResolver;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\Concrete as ConcreteObject;
use OpenDxp\Model\Document;
use OpenDxp\Workflow\Manager;
use OpenDxp\Workflow\Notes\CustomHtmlServiceInterface;
use OpenDxp\Workflow\Transition;
use Symfony\Component\Workflow\Registry;

final class GetModalCustomHtmlHandler
{
    public function __construct(
        private readonly Manager $workflowManager,
        private readonly Registry $workflowRegistry,
        private readonly WorkflowElementResolver $elementResolver,
    ) {}

    public function __invoke(GetModalCustomHtmlPayload $payload): GetModalCustomHtmlResult
    {
        $element = $this->elementResolver->resolve($payload->ctype, $payload->cid);

        $workflow = $this->workflowRegistry->get($element, $payload->workflowName);

        if ($payload->isGlobalAction) {
            $globalAction = $this->workflowManager->getGlobalAction($workflow->getName(), $payload->transition);
            if ($globalAction) {
                return new GetModalCustomHtmlResult(
                    customHtml: $this->buildCustomHtml($globalAction->getCustomHtmlService(), $element),
                );
            }
        } elseif ($workflow->can($element, $payload->transition)) {
            $enabledTransitions = $workflow->getEnabledTransitions($element);
            $matchedTransition = null;
            foreach ($enabledTransitions as $_transition) {
                if ($_transition->getName() === $payload->transition) {
                    $matchedTransition = $_transition;
                }
            }

            if ($matchedTransition instanceof Transition) {
                return new GetModalCustomHtmlResult(
                    customHtml: $this->buildCustomHtml($matchedTransition->getCustomHtmlService(), $element),
                );
            }
        }

        throw new AdminOperationFailedException('error validating the action on this element, element cannot perform this action');
    }

    private function buildCustomHtml(?CustomHtmlServiceInterface $customHtmlService, ConcreteObject|Document|Asset $element): array
    {
        $customHtml = [];
        if ($customHtmlService) {
            foreach (['top', 'center', 'bottom'] as $position) {
                $customHtml[$position] = $customHtmlService->renderHtmlForRequestedPosition($element, $position);
            }
        }

        return $customHtml;
    }
}
