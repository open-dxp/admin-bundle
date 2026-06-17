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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\DeleteDocument;

use OpenDxp\Logger;
use OpenDxp\Model\Document;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DeleteDocumentHandler
{
    public function __invoke(DeleteDocumentPayload $payload): DeleteDocumentResult
    {
        if ($payload->type === 'children') {
            $parentDocument = Document::getById($payload->id);

            if (!$parentDocument) {
                throw new NotFoundHttpException('Parent document not found');
            }

            $list = new Document\Listing();
            $list->setCondition('`path` LIKE ?', [$list->escapeLike($parentDocument->getRealFullPath()) . '/%']);
            $list->setLimit($payload->amount);
            $list->setOrderKey('LENGTH(`path`)', false);
            $list->setOrder('DESC');

            $documents = $list->load();

            $deletedItems = [];
            foreach ($documents as $document) {
                $deletedItems[$document->getId()] = $document->getRealFullPath();
                if ($document->isAllowed('delete') && !$document->isLocked()) {
                    $document->delete();
                }
            }

            return new DeleteDocumentResult($deletedItems);
        }

        $document = Document::getById($payload->id);

        if (!$document) {
            throw new NotFoundHttpException('Document not found');
        }

        if (!$document->isAllowed('delete')) {
            throw new AccessDeniedHttpException('Access denied: missing delete permission');
        }

        if ($document->isLocked()) {
            throw new RuntimeException('Prevented deleting document, because it is locked: ID: ' . $document->getId());
        }

        $document->delete();

        return new DeleteDocumentResult();
    }
}
