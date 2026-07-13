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

namespace OpenDxp\Bundle\AdminBundle\Handler\Workflow\ShowGraph\GetWorkflowSvg;

use InvalidArgumentException;
use OpenDxp\Bundle\AdminBundle\Handler\Workflow\ShowGraph\ShowGraphPayload;
use OpenDxp\Bundle\AdminBundle\Service\Workflow\WorkflowElementResolver;
use OpenDxp\Tool\Console;
use OpenDxp\Workflow\Manager;
use Symfony\Component\Process\Process;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GetWorkflowSvgHandler
{
    public function __construct(
        private readonly Manager $workflowManager,
        private readonly TranslatorInterface $translator,
        private readonly WorkflowElementResolver $elementResolver,
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(ShowGraphPayload $payload): GetWorkflowSvgResult
    {
        $element = $this->elementResolver->resolve($payload->ctype, $payload->cid);

        $workflow = $this->workflowManager->getWorkflowByName($payload->workflowName);
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

        return new GetWorkflowSvgResult($process->getOutput());
    }
}
