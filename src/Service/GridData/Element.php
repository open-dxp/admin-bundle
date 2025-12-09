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

namespace OpenDxp\Bundle\AdminBundle\Service\GridData;

use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;

/**
 *
 * @internal
 */
abstract class Element
{
    public static function gridElementData(ElementInterface $element): array
    {
        $data = [
            'id' => $element->getId(),
            'fullpath' => $element->getRealFullPath(),
            'type' => Service::getElementType($element),
            'subtype' => $element->getType(),
            'filename' => $element->getKey(),
            'creationDate' => $element->getCreationDate(),
            'modificationDate' => $element->getModificationDate(),
        ];

        $data['published'] = method_exists($element, 'isPublished') ? $element->isPublished() : true;

        return $data;
    }
}
