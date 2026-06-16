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

use OpenDxp\Model\Element\Note;

final class DeleteNoteHandler
{
    public function __invoke(int $id): bool
    {
        $note = Note::getById($id);

        if ($note && !$note->getLocked()) {
            $note->delete();

            return true;
        }

        return false;
    }
}
