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

use OpenDxp\Model;

/**
 * @internal
 */
class Document extends Element
{
    public static function getData(Model\Document $document): array
    {
        $data = self::gridElementData($document);

        if ($document instanceof Model\Document\Page) {
            $data['title'] = $document->getTitle();
            $data['description'] = $document->getDescription();
        } else {
            $data['title'] = '';
            $data['description'] = '';
            $data['name'] = '';
        }

        return $data;
    }
}
