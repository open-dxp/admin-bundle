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

namespace OpenDxp\Bundle\AdminBundle\Handler\Email;

use OpenDxp\Model\Tool;

final class UpdateBlocklistEntryHandler
{
    public function __invoke(array $data): array
    {
        $address = Tool\Email\Blocklist::getByAddress($data['address']);
        $address->setValues($data);
        $address->save();

        return $address->getObjectVars();
    }
}
