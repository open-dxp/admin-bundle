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
