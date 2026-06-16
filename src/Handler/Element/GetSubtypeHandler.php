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

namespace OpenDxp\Bundle\AdminBundle\Handler\Element;

use OpenDxp;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Event\Model\ResolveElementEvent;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetSubtypeHandler
{
    public function __invoke(string $id, ?string $type): GetSubtypeResult
    {
        $idOrPath = trim($id);

        $event = new ResolveElementEvent($type, $idOrPath);
        OpenDxp::getEventDispatcher()->dispatch($event, AdminEvents::RESOLVE_ELEMENT);
        $idOrPath = $event->getId();
        $resolvedType = $event->getType();

        if (is_numeric($idOrPath)) {
            $el = Element\Service::getElementById($resolvedType, (int) $idOrPath);
        } elseif ($resolvedType === 'document') {
            $el = Document\Service::getByUrl($idOrPath);
        } else {
            $el = Element\Service::getElementByPath($resolvedType, $idOrPath);
        }

        if (!$el) {
            throw new NotFoundHttpException('Element not found');
        }

        $subtype = null;
        if ($el instanceof Asset || $el instanceof Document) {
            $subtype = $el->getType();
        } elseif ($el instanceof DataObject\Concrete) {
            $subtype = $el->getClassName();
        } elseif ($el instanceof DataObject\Folder) {
            $subtype = 'folder';
        }

        return new GetSubtypeResult(subtype: $subtype, id: $el->getId(), type: $type);
    }
}
