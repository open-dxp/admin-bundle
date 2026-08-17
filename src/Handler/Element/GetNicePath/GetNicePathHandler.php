<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetNicePath;

use Exception;
use OpenDxp\Logger;
use OpenDxp\Model\DataObject;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class GetNicePathHandler
{
    /**
     * @throws Exception
     */
    public function __invoke(GetNicePathPayload $payload): GetNicePathResult
    {
        $source = $payload->source;
        if ($source['type'] !== 'object') {
            throw new BadRequestHttpException('currently only objects as source elements are supported');
        }

        $result = [];
        $id = $source['id'];
        $sourceObject = DataObject\Concrete::getById($id);

        $ownerType = $payload->context['containerType'];
        $fieldname = $payload->context['fieldname'];

        $fd = $this->getFieldDefinition($sourceObject, $payload->context);
        $result = $this->convertResultWithPathFormatter($sourceObject, $payload->context, $result, $payload->targets);

        if ($payload->loadEditModeData) {
            $methodName = 'get' . ucfirst($fieldname);
            if ($ownerType === 'object' && method_exists($sourceObject, $methodName)) {
                $data = DataObject\Service::useInheritedValues(true, [$sourceObject, $methodName]);
                $editModeData = $fd->getDataForEditmode($data, $sourceObject);
                // Inherited values show as an empty array
                if (is_array($editModeData) && $editModeData !== []) {
                    foreach ($editModeData as $relationObjectAttribute) {
                        $relationObjectAttribute['$$nicepath'] = isset($relationObjectAttribute[$payload->idProperty], $result[$relationObjectAttribute[$payload->idProperty]])
                            ? $result[$relationObjectAttribute[$payload->idProperty]]
                            : null;

                        $result[$relationObjectAttribute[$payload->idProperty]] = $relationObjectAttribute;
                    }
                } else {
                    foreach ($result as $resultItemId => $resultItem) {
                        $result[$resultItemId] = ['$$nicepath' => $resultItem];
                    }
                }
            } else {
                Logger::error('Loading edit mode data is not supported for ownertype: ' . $ownerType);
            }
        }

        return new GetNicePathResult(data: $result);
    }

    /**
     * @throws Exception
     */
    private function getFieldDefinition(DataObject\Concrete $source, array $context): DataObject\ClassDefinition\Data|bool|null
    {
        $ownerType = $context['containerType'];
        $fieldname = $context['fieldname'];
        $fd = null;

        if ($ownerType === 'object') {
            $subContainerType = $context['subContainerType'] ?? null;
            if ($subContainerType) {
                $subContainerKey = $context['subContainerKey'];
                $subContainer = $source->getClass()->getFieldDefinition($subContainerKey);
                if (method_exists($subContainer, 'getFieldDefinition')) {
                    $fd = $subContainer->getFieldDefinition($fieldname);
                }
            } else {
                $fd = $source->getClass()->getFieldDefinition($fieldname);
            }
        } elseif ($ownerType === 'localizedfield') {
            $localizedfields = $source->getClass()->getFieldDefinition('localizedfields');
            if ($localizedfields instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                $fd = $localizedfields->getFieldDefinition($fieldname);
            }
        } elseif ($ownerType === 'objectbrick') {
            $fdBrick = DataObject\Objectbrick\Definition::getByKey($context['containerKey']);
            $fd = $fdBrick->getFieldDefinition($fieldname);
        } elseif ($ownerType === 'fieldcollection') {
            $containerKey = $context['containerKey'];
            $fdCollection = DataObject\Fieldcollection\Definition::getByKey($containerKey);
            if (($context['subContainerType'] ?? null) === 'localizedfield') {
                /** @var DataObject\ClassDefinition\Data\Localizedfields $fdLocalizedFields */
                $fdLocalizedFields = $fdCollection->getFieldDefinition('localizedfields');
                $fd = $fdLocalizedFields->getFieldDefinition($fieldname);
            } else {
                $fd = $fdCollection->getFieldDefinition($fieldname);
            }
        }

        return $fd;
    }

    /**
     * @throws Exception
     */
    private function convertResultWithPathFormatter(DataObject\Concrete $source, array $context, array $result, array $targets): array
    {
        $fd = $this->getFieldDefinition($source, $context);

        if ($fd instanceof DataObject\ClassDefinition\PathFormatterAwareInterface) {
            $formatter = $fd->getPathFormatterClass();

            if (null !== $formatter) {
                $pathFormatter = DataObject\ClassDefinition\Helper\PathFormatterResolver::resolvePathFormatter(
                    $fd->getPathFormatterClass()
                );

                if ($pathFormatter instanceof DataObject\ClassDefinition\PathFormatterInterface) {
                    $result = $pathFormatter->formatPath($result, $source, $targets, [
                        'fd' => $fd,
                        'context' => $context,
                    ]);
                }
            }
        }

        return $result;
    }
}
