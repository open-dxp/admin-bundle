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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Link\GetLinkData;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Normalizer\ElementResponseNormalizer;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdQueryPayload;
use OpenDxp\Bundle\AdminBundle\Service\Element\EditLockService;
use OpenDxp\Model\Document\Link;
use OpenDxp\Model\Schedule\Task;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;

final class GetLinkDataHandler
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly EditLockService $editLockService,
        private readonly ElementResponseNormalizer $normalizer,
    ) {}

    public function __invoke(IdQueryPayload $payload): GetLinkDataResult
    {
        $link = Link::getById($payload->id);
        if (!$link) {
            throw new NotFoundHttpException('Link not found');
        }

        if ($link->isAllowed('save') || $link->isAllowed('publish') || $link->isAllowed('unpublish') || $link->isAllowed('delete')) {
            $this->editLockService->checkAndAcquire($link->getId(), 'document', AdminEvents::DOCUMENT_GET_IS_LOCKED, $link);
        }

        $cloned = clone $link;
        $cloned->setElement(null);
        $cloned->setParent(null);

        $data = $this->serializer->serialize($cloned->getObjectVars(), 'json', []);
        $data = json_decode($data, true);
        $data['locked'] = $cloned->isLocked();
        $data['rawHref'] = $cloned->getRawHref();
        $data['scheduledTasks'] = array_map(
            static fn (Task $task) => $task->getObjectVars(),
            $cloned->getScheduledTasks()
        );

        $this->normalizer->normalize($cloned, $data, self::class);

        return new GetLinkDataResult(original: $link, link: $cloned, data: $data);
    }
}
