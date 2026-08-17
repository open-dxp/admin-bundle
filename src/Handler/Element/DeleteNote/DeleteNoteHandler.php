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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\DeleteNote;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Handler\Element\NoteListPayload;
use OpenDxp\Model\Element\Note;

final class DeleteNoteHandler
{
    public function __invoke(NoteListPayload $payload): void
    {
        $note = Note::getById($payload->id);

        if (!$note) {
            throw new AdminOperationFailedException('note_not_found');
        }

        if ($note->getLocked()) {
            throw new AdminOperationFailedException('note_is_locked');
        }

        $note->delete();
    }
}
