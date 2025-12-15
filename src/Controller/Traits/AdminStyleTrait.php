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

use Exception;
use OpenDxp;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Event\ElementAdminStyleEvent;
use OpenDxp\Model\Element\AdminStyle;
use OpenDxp\Model\Element\ElementInterface;

/**
 * @internal
 */
trait AdminStyleTrait
{
    /**
     * @throws Exception
     */
    protected function addAdminStyle(ElementInterface $element, ?int $context = null, array &$data = []): void
    {
        $event = new ElementAdminStyleEvent($element, new AdminStyle($element), $context);
        OpenDxp::getEventDispatcher()->dispatch($event, AdminEvents::RESOLVE_ELEMENT_ADMIN_STYLE);
        $adminStyle = $event->getAdminStyle();

        $data['iconCls'] = $adminStyle->getElementIconClass() !== false ? $adminStyle->getElementIconClass() : null;
        if (!$data['iconCls']) {
            $data['icon'] = $adminStyle->getElementIcon() !== false ? $adminStyle->getElementIcon() : null;
        } else {
            $data['icon'] = null;
        }
        if ($adminStyle->getElementCssClass() !== false) {
            if (!isset($data['cls'])) {
                $data['cls'] = '';
            }
            $data['cls'] .= $adminStyle->getElementCssClass() . ' ';
        }
        $data['qtipCfg'] = $adminStyle->getElementQtipConfig();

        $elementText = $adminStyle->getElementText();
        if ($elementText !== null) {
            $data['text'] = $elementText;
        }

    }
}
