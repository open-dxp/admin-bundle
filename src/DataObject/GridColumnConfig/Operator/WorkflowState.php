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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator;

use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Workflow\Place\StatusInfo;
use stdClass;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @internal
 */
final class WorkflowState extends AbstractOperator
{
    private StatusInfo $statusInfo;

    public function getLabeledValue(array|ElementInterface $element): stdClass
    {
        $result = new stdClass();
        $result->label = $this->label;

        $context = $this->getContext();
        $purpose = $context['purpose'] ?? null;

        if ($purpose === 'gridview') {
            $result->value = $this->statusInfo->getAllPalacesHtml($element);
        } else {
            $result->value = $this->statusInfo->getAllPlacesForCsv($element);
        }

        return $result;
    }

    #[Required]
    public function setWorkflowStatusInfo(StatusInfo $statusInfo): void
    {
        $this->statusInfo = $statusInfo;
    }
}
