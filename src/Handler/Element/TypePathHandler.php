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

use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class TypePathHandler
{
    public function __invoke(int $id, ?string $type): TypePathResult
    {
        if ($type === 'asset') {
            $element = Asset::getById($id);
        } elseif ($type === 'document') {
            $element = Document::getById($id);
        } else {
            $element = DataObject::getById($id);
        }

        if (!$element) {
            throw new NotFoundHttpException('Element not found');
        }

        return new TypePathResult(
            index: method_exists($element, 'getIndex') ? (int) $element->getIndex() : 0,
            idPath: Element\Service::getIdPath($element),
            typePath: Element\Service::getTypePath($element),
            fullpath: $element->getRealFullPath(),
            sortIndexPath: $type !== 'asset' ? Element\Service::getSortIndexPath($element) : null,
        );
    }
}
