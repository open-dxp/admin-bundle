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

namespace OpenDxp\Bundle\AdminBundle\Controller\Traits;

use OpenDxp\Bundle\AdminBundle\Service\ElementServiceInterface;
use OpenDxp\Model\Element\ElementInterface;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @internal
 *
 * @todo Needs to be replaced by ElementService. Note: Has an optional dependency in the OpenDxpSeoBundle (Needs to be adopted before removing/deprecating this).
 */
trait DocumentTreeConfigTrait
{
    protected ElementServiceInterface $elementService;

    #[Required]
    public function setElementService(ElementServiceInterface $elementService): void
    {
        $this->elementService = $elementService;
    }

    public function getTreeNodeConfig(ElementInterface $element): array
    {
        return $this->elementService->getElementTreeNodeConfig($element);
    }
}
