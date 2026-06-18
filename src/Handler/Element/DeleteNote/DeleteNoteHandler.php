<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\DeleteNote;

use OpenDxp\Model\Element\Note;
use OpenDxp\Bundle\AdminBundle\Handler\Element\NoteListPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class DeleteNoteHandler
{
    public function __invoke(NoteListPayload $payload): void
    {
        $note = Note::getById($payload->id);

        if (!$note) {
            return;
        }

        if ($note->getLocked()) {
            throw new BadRequestHttpException('note_is_locked');
        }

        $note->delete();
    }
}
