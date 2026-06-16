<?php

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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Document;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Exception\Document\DocumentNotFoundException;
use OpenDxp\Bundle\AdminBundle\Normalizer\ElementResponseNormalizer;
use OpenDxp\Bundle\AdminBundle\Service\Element\EditLockService;
use OpenDxp\Model\Document;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class GetDocumentDataHandler
{
    public function __construct(
        private readonly EditLockService $editLockService,
        private readonly ElementResponseNormalizer $normalizer,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(int $id): GetDocumentDataResult
    {
        $document = Document::getById($id);
        if (!$document instanceof Document) {
            throw new DocumentNotFoundException($id);
        }

        if (!$document->isAllowed('view')) {
            throw new AccessDeniedHttpException();
        }

        if ($document->isAllowed('save') || $document->isAllowed('publish') || $document->isAllowed('unpublish') || $document->isAllowed('delete')) {
            $this->editLockService->checkAndAcquire($document->getId(), 'document', AdminEvents::DOCUMENT_GET_IS_LOCKED, $document);
        }

        $document = clone $document;
        $data = $document->getObjectVars();

        $this->normalizer->normalize($document, $data, self::class);

        $event = new GenericEvent($this, ['data' => $data, 'document' => $document]);
        $this->eventDispatcher->dispatch($event, AdminEvents::DOCUMENT_GET_PRE_SEND_DATA);
        $data = $event->getArgument('data');

        return new GetDocumentDataResult($data);
    }
}
