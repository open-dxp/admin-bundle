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

use OpenDxp\Model\Element;

final class AddNoteHandler
{
    public function __invoke(
        int $cid,
        string $ctype,
        ?string $title,
        ?string $description,
        ?string $type,
    ): void {
        $note = new Element\Note();
        $note->setCid($cid);
        $note->setCtype($ctype);
        $note->setDate(time());
        $note->setTitle($title);
        $note->setDescription($description);
        $note->setType($type);
        $note->setLocked(false);
        $note->save();
    }
}
