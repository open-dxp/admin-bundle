<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\AnalyzePermissions;

use OpenDxp\Model;
use OpenDxp\Model\Element;

final class AnalyzePermissionsHandler
{
    public function __invoke(AnalyzePermissionsPayload $payload): AnalyzePermissionsResult
    {
        if ($payload->userId) {
            $userList = [];
            if ($user = Model\User::getById($payload->userId)) {
                $userList[] = $user;
            }
        } else {
            $userList = new Model\User\Listing();
            $userList->setCondition('`type` = ?', ['user']);
            $userList = $userList->load();
        }

        $element = Element\Service::getElementById($payload->elementType, $payload->elementId);
        $result = Element\PermissionChecker::check($element, $userList);

        return new AnalyzePermissionsResult(data: $result);
    }
}
