<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\TypePath;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element;

final class TypePathHandler
{
    public function __invoke(TypePathPayload $payload): TypePathResult
    {
        if ($payload->type === 'asset') {
            $element = Asset::getById($payload->id);
        } elseif ($payload->type === 'document') {
            $element = Document::getById($payload->id);
        } else {
            $element = DataObject::getById($payload->id);
        }

        if (!$element) {
            throw new AdminOperationFailedException('Element not found');
        }

        return new TypePathResult(
            index: method_exists($element, 'getIndex') ? (int) $element->getIndex() : 0,
            idPath: Element\Service::getIdPath($element),
            typePath: Element\Service::getTypePath($element),
            fullpath: $element->getRealFullPath(),
            sortIndexPath: $payload->type !== 'asset' ? Element\Service::getSortIndexPath($element) : null,
        );
    }
}
