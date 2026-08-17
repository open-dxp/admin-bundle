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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Page\GeneratePagePreviews;

use OpenDxp\Messenger\GeneratePagePreviewMessage;
use OpenDxp\Model\Document;
use Symfony\Component\Messenger\MessageBusInterface;

final class GeneratePagePreviewsHandler
{
    public function __construct(
        private readonly MessageBusInterface $messengerBusOpendxpCore,
    ) {}

    public function __invoke(): void
    {
        $list = new Document\Listing();
        $list->setCondition('`type` = ?', ['page']);

        // @todo: this seems completely wrong.
        foreach ($list->loadIdList() as $docId) {
            $this->messengerBusOpendxpCore->dispatch(
                new GeneratePagePreviewMessage($docId, \OpenDxp\Tool::getHostUrl())
            );

            break;
        }
    }
}
