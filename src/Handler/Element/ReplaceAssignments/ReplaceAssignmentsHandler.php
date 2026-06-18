<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\ReplaceAssignments;

use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element;
use OpenDxp\Model\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class ReplaceAssignmentsHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(ReplaceAssignmentsPayload $payload): void
    {
        $adminUser = $this->userContext->getAdminUser();
        $element = Element\Service::getElementById($payload->type, $payload->id);
        $sourceEl = Element\Service::getElementById($payload->sourceType, $payload->sourceId);
        $targetEl = Element\Service::getElementById($payload->targetType, $payload->targetId);

        if (!$element || !$sourceEl || !$targetEl) {
            throw new NotFoundHttpException('One or more elements not found');
        }

        if ($payload->sourceType !== $payload->targetType || $sourceEl->getType() !== $targetEl->getType()) {
            throw new BadRequestHttpException('source-type and target-type do not match');
        }

        if (!$element->isAllowed('save')) {
            throw new AccessDeniedHttpException();
        }

        $rewriteConfig = [
            $payload->sourceType => [
                $sourceEl->getId() => $targetEl->getId(),
            ],
        ];

        if ($element instanceof Document) {
            $element = Document\Service::rewriteIds($element, $rewriteConfig);
        } elseif ($element instanceof DataObject\AbstractObject) {
            $element = DataObject\Service::rewriteIds($element, $rewriteConfig);
        } elseif ($element instanceof Asset) {
            $element = Asset\Service::rewriteIds($element, $rewriteConfig);
        }

        $element->setUserModification($adminUser->getId());
        $element->save();
    }
}
