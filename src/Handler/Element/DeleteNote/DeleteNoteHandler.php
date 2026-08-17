<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\DeleteNote;

use OpenDxp\Model\Element\Note;
use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Handler\Element\NoteListPayload;

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
