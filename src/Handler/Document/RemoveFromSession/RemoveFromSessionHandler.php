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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\RemoveFromSession;

use OpenDxp\Bundle\AdminBundle\Service\Element\ElementDraftService;

final class RemoveFromSessionHandler
{
    public function __construct(private readonly ElementDraftService $elementDraftService,)
    {
    }

    public function __invoke(RemoveFromSessionPayload $payload): void
    {
        $this->elementDraftService->removeDocument($payload->id);
    }
}
