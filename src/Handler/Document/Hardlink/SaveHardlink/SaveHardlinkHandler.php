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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Hardlink\SaveHardlink;

use OpenDxp\Bundle\AdminBundle\Handler\Document\Hardlink\SaveHardlink\SaveHardlinkPayload;
use OpenDxp\Bundle\AdminBundle\Service\Document\DocumentPayloadMapper;
use OpenDxp\Bundle\AdminBundle\Service\Document\DocumentPersistenceCoordinator;
use OpenDxp\Model\Document\Hardlink;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class SaveHardlinkHandler
{
    public function __construct(
        private readonly DocumentPayloadMapper $mapper,
        private readonly DocumentPersistenceCoordinator $coordinator,
    ) {}

    public function __invoke(SaveHardlinkPayload $payload): SaveHardlinkResult
    {
        $link = Hardlink::getById($payload->id);
        if (!$link) {
            throw new NotFoundHttpException('Hardlink not found');
        }

        $this->mapper->applyHardlinkPayload($payload, $link);
        $result = $this->coordinator->save($link, $payload->task);

        return new SaveHardlinkResult(
            link: $result->document instanceof Hardlink ? $result->document : $link,
            task: $result->task,
            treeData: $result->treeData,
        );
    }
}
