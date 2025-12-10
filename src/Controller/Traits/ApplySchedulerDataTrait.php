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

namespace OpenDxp\Bundle\AdminBundle\Controller\Traits;

use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Schedule\Task;
use OpenDxp\Model\User;
use OpenDxp\Security\User\User as UserProxy;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
trait ApplySchedulerDataTrait
{
    protected function applySchedulerDataToElement(Request $request, ElementInterface $element, UserProxy|User|null $adminUser): void
    {
        // scheduled tasks
        if ($request->get('scheduler')) {
            $tasks = [];
            $tasksData = $this->decodeJson($request->get('scheduler'));

            if (!empty($tasksData)) {
                foreach ($tasksData as $taskData) {
                    $taskData['userId'] = $adminUser?->getId();

                    $task = new Task($taskData);
                    $tasks[] = $task;
                }
            }

            if ($element->isAllowed('settings') && method_exists($element, 'setScheduledTasks')) {
                $element->setScheduledTasks($tasks);
            }
        }
    }
}
