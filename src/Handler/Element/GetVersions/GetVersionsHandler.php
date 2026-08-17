<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetVersions;

use OpenDxp\Model;
use OpenDxp\Model\Element;
use OpenDxp\Model\User;
use OpenDxp\Model\Version;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;

final class GetVersionsHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(GetVersionsPayload $payload): GetVersionsResult
    {
        $adminUser = $this->userContext->getAdminUser();
        $allowedTypes = ['asset', 'document', 'object'];

        if (!$payload->id || !in_array($payload->elementType, $allowedTypes)) {
            throw new NotFoundHttpException('Element type not found');
        }

        $element = Model\Element\Service::getElementById($payload->elementType, $payload->id);
        if (!$element) {
            throw new NotFoundHttpException($payload->elementType . ' with id [' . $payload->id . "] doesn't exist");
        }

        if (!$element->isAllowed('versions')) {
            throw new AccessDeniedHttpException('Permission denied, ' . $payload->elementType . ' id [' . $payload->id . ']');
        }

        $schedule = $element->getScheduledTasks();
        $schedules = [];
        foreach ($schedule as $task) {
            if ($task->getActive()) {
                $schedules[$task->getVersion()] = $task->getDate();
            }
        }

        $list = new Version\Listing();
        $list->setLoadAutoSave(true);
        $list->setCondition('cid = ? AND ctype = ? AND (autoSave=0 OR (autoSave=1 AND userId = ?)) ', [
            $element->getId(),
            Element\Service::getElementType($element),
            $adminUser->getId(),
        ])
            ->setOrderKey('date')
            ->setOrder('ASC');

        $versions = $list->load();
        $versions = Model\Element\Service::getSafeVersionInfo($versions);
        $versions = array_reverse($versions);

        foreach ($versions as &$version) {
            $version['scheduled'] = null;
            if (array_key_exists($version['id'], $schedules)) {
                $version['scheduled'] = $schedules[$version['id']];
            }
        }

        return new GetVersionsResult(versions: $versions);
    }
}
