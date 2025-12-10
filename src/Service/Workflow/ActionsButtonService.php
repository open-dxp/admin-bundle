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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Service\Workflow;

use OpenDxp\Model\DataObject\AbstractObject;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Workflow\Manager;
use OpenDxp\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

class ActionsButtonService
{
    public function __construct(private readonly Manager $workflowManager)
    {
    }

    public function getAllowedTransitions(WorkflowInterface $workflow, ElementInterface $element): array
    {
        $allowedTransitions = [];

        /**
         * @var Transition $transition
         */
        foreach ($workflow->getEnabledTransitions($element) as $transition) {
            if (($notes = $transition->getNotes()) && $element instanceof AbstractObject) {
                $notes = $this->enrichNotes($element, $notes);
            }

            $allowedTransitions[] = [
                'name' => $transition->getName(),
                'label' => $transition->getLabel(),
                'iconCls' => $transition->getIconClass(),
                'objectLayout' => $transition->getObjectLayout(),
                'notes' => $notes,
                'unsavedChangesBehaviour' => $transition->getOptions()['unsavedChangesBehaviour'],
            ];
        }

        return $allowedTransitions;
    }

    public function getGlobalActions(WorkflowInterface $workflow, ElementInterface $element): array
    {
        $globalActions = [];
        foreach ($this->workflowManager->getGlobalActions($workflow->getName()) as $globalAction) {
            if ($globalAction->isGuardValid($workflow, $element)) {
                if (($notes = $globalAction->getNotes()) && $element instanceof AbstractObject) {
                    $notes = $this->enrichNotes($element, $notes);
                }

                $globalActions[] = [
                    'name' => $globalAction->getName(),
                    'label' => $globalAction->getLabel(),
                    'iconCls' => $globalAction->getIconClass(),
                    'objectLayout' => $globalAction->getObjectLayout(),
                    'notes' => $notes,
                ];
            }
        }

        return $globalActions;
    }

    private function enrichNotes(AbstractObject $object, array $notes): array
    {
        if (!empty($notes['commentGetterFn'])) {
            $commentGetterFn = $notes['commentGetterFn'];
            $notes['commentPrefill'] = $object->$commentGetterFn();
        } elseif ($notes !== []) {
            $notes['commentPrefill'] = '';
        }

        return $notes;
    }
}
