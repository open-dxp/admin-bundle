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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Link;

use OpenDxp\Bundle\AdminBundle\Payload\Document\LinkPayload;
use OpenDxp\Bundle\AdminBundle\Service\Document\DocumentPayloadMapper;
use OpenDxp\Bundle\AdminBundle\Service\Document\DocumentPersistenceCoordinator;
use OpenDxp\Model\Document\Link;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class SaveLinkHandler
{
    public function __construct(
        private readonly DocumentPayloadMapper $mapper,
        private readonly DocumentPersistenceCoordinator $coordinator,
    ) {}

    public function __invoke(int $id, LinkPayload $payload): SaveLinkResult
    {
        $link = Link::getById($id);
        if (!$link) {
            throw new NotFoundHttpException('Link not found');
        }

        $this->mapper->applyLinkPayload($payload, $link);
        $result = $this->coordinator->save($link, $payload->task);

        return new SaveLinkResult(
            link: $result->document instanceof Link ? $result->document : $link,
            task: $result->task,
            treeData: $result->treeData,
        );
    }
}
