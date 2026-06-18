<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\AddNote;

use OpenDxp\Model\Element;

final class AddNoteHandler
{
    public function __invoke(AddNotePayload $payload): void
    {
        $note = new Element\Note();
        $note->setCid($payload->cid);
        $note->setCtype($payload->ctype);
        $note->setDate(time());
        $note->setTitle($payload->title);
        $note->setDescription($payload->description);
        $note->setType($payload->type);
        $note->setLocked(false);
        $note->save();
    }
}
