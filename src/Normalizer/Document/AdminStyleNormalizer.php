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

namespace OpenDxp\Bundle\AdminBundle\Normalizer\Document;

use OpenDxp;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Event\ElementAdminStyleEvent;
use OpenDxp\Bundle\AdminBundle\Normalizer\ElementResponseNormalizerInterface;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\AdminStyle;
use OpenDxp\Model\Element\ElementInterface;

final class AdminStyleNormalizer implements ElementResponseNormalizerInterface
{
    public function supports(ElementInterface $element, string $handlerClass): bool
    {
        return $element instanceof Document;
    }

    public function normalize(ElementInterface $element, array &$data, array $context = []): void
    {
        $event = new ElementAdminStyleEvent($element, new AdminStyle($element), ElementAdminStyleEvent::CONTEXT_EDITOR);
        OpenDxp::getEventDispatcher()->dispatch($event, AdminEvents::RESOLVE_ELEMENT_ADMIN_STYLE);
        $adminStyle = $event->getAdminStyle();

        $data['iconCls'] = $adminStyle->getElementIconClass() !== false ? $adminStyle->getElementIconClass() : null;
        $data['icon'] = !$data['iconCls'] && $adminStyle->getElementIcon() !== false ? $adminStyle->getElementIcon() : null;

        if ($adminStyle->getElementCssClass() !== false) {
            $data['cls'] = ($data['cls'] ?? '') . $adminStyle->getElementCssClass() . ' ';
        }

        $data['qtipCfg'] = $adminStyle->getElementQtipConfig();

        $elementText = $adminStyle->getElementText();
        if ($elementText !== null) {
            $data['text'] = $elementText;
        }
    }
}
