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

namespace OpenDxp\Bundle\AdminBundle\Handler\Workflow\GetWorkflowDetails;

use InvalidArgumentException;
use OpenDxp\Bundle\AdminBundle\Service\Workflow\ActionsButtonService;
use OpenDxp\Bundle\AdminBundle\Service\Workflow\WorkflowElementResolver;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\Concrete as ConcreteObject;
use OpenDxp\Model\Document;
use OpenDxp\Tool\Console;
use OpenDxp\Workflow\Manager;
use OpenDxp\Workflow\Place\StatusInfo;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GetWorkflowDetailsHandler
{
    public function __construct(
        private readonly Manager $workflowManager,
        private readonly StatusInfo $placeStatusInfo,
        private readonly RouterInterface $router,
        private readonly ActionsButtonService $actionsButtonService,
        private readonly TranslatorInterface $translator,
        private readonly WorkflowElementResolver $elementResolver,
    ) {}

    public function __invoke(GetWorkflowDetailsPayload $payload): GetWorkflowDetailsResult
    {
        $element = $this->elementResolver->resolve($payload->ctype, $payload->cid);

        $data = [];

        foreach ($this->workflowManager->getAllWorkflowsForSubject($element) as $workflow) {
            $workflowConfig = $this->workflowManager->getWorkflowConfig($workflow->getName());

            $svg = null;
            $msg = '';

            try {
                $svg = $this->getWorkflowSvg($workflow, $element);
            } catch (InvalidArgumentException $e) {
                $msg = $e->getMessage();
            }

            $url = $this->router->generate(
                'opendxp_admin_workflow_show_graph',
                [
                    'cid' => $payload->cid,
                    'ctype' => $payload->ctype,
                    'workflow' => $workflow->getName(),
                ]
            );

            $allowedTransitions = $this->actionsButtonService->getAllowedTransitions($workflow, $element);
            $globalActions = $this->actionsButtonService->getGlobalActions($workflow, $element);

            $data[] = [
                'workflowName' => $this->translator->trans($workflowConfig->getLabel(), [], 'admin'),
                'placeInfo' => $this->placeStatusInfo->getAllPalacesHtml($element, $workflow->getName()),
                'graph' => $msg ?: '<a href="' . $url . '" target="_blank"><div class="workflow-graph-preview">' . $svg . '</div></a>',
                'allowedTransitions' => $allowedTransitions,
                'globalActions' => $globalActions,
            ];
        }

        return new GetWorkflowDetailsResult(data: $data, total: count($data));
    }

    private function getWorkflowSvg(WorkflowInterface $workflow, ConcreteObject|Document|Asset $element): string
    {
        $marking = $workflow->getMarking($element);

        $php = Console::getExecutable('php');
        $dot = Console::getExecutable('dot');

        if (!$php) {
            throw new InvalidArgumentException($this->translator->trans('workflow_cmd_not_found', ['php'], 'admin'));
        }

        if (!$dot) {
            throw new InvalidArgumentException($this->translator->trans('workflow_cmd_not_found', ['dot'], 'admin'));
        }

        $cmd = $php . ' ' . OPENDXP_PROJECT_ROOT . '/bin/console opendxp:workflow:dump ${WNAME} ${WPLACES} | ${DOT} -Tsvg';
        $params = [
            'WNAME' => $workflow->getName(),
            'WPLACES' => implode(' ', array_keys($marking->getPlaces())),
            'DOT' => $dot,
        ];

        Console::addLowProcessPriority($cmd);
        $process = Process::fromShellCommandline($cmd);
        $process->run(null, $params);

        return $process->getOutput();
    }
}
