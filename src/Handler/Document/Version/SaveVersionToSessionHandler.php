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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Version;

use OpenDxp\Bundle\AdminBundle\Exception\Document\DocumentNotFoundException;
use OpenDxp\Bundle\AdminBundle\Service\Element\SessionService;
use OpenDxp\Model\Document;
use OpenDxp\Model\Version;

final class SaveVersionToSessionHandler
{
    public function __construct(private readonly SessionService $sessionService) {}

    public function __invoke(int $versionId): void
    {
        $version = Version::getById($versionId);
        $document = $version?->loadData();

        if (!$document instanceof Document) {
            throw new DocumentNotFoundException($versionId);
        }

        $this->sessionService->saveDocument($document);
    }
}
