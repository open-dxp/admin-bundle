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

namespace OpenDxp\Bundle\AdminBundle\Handler\Recyclebin\RestoreRecyclebinItem;

use OpenDxp\Model\Element\Recyclebin;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class RestoreRecyclebinItemHandler
{
    public function __invoke(RestoreRecyclebinItemPayload $payload): void
    {
        $item = Recyclebin\Item::getById($payload->id);
        if (!$item) {
            throw new NotFoundHttpException(sprintf('Recyclebin item with id %d not found', $payload->id));
        }

        $item->restore();
    }
}
