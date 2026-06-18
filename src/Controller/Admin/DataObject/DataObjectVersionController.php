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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\DataObject;

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Version\DiffVersions\DiffVersionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Version\DiffVersions\DiffVersionsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Version\PreviewVersion\PreviewVersionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Version\PreviewVersion\PreviewVersionPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Version\PublishVersion\PublishVersionHandler;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdBodyPayload;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Tool;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;
use Twig\Extension\CoreExtension;

/**
 * @internal
 */
#[Route('/object', name: 'opendxp_admin_dataobject_dataobject_')]
#[IsGranted(CorePermission::Objects->value)]
class DataObjectVersionController extends AdminAbstractController
{
    #[Route('/publish-version', name: 'publishversion', methods: ['POST'])]
    public function publishVersionAction(IdBodyPayload $payload, PublishVersionHandler $publishVersion): JsonResponse
    {
        $result = $publishVersion($payload);

        return $this->adminJson(ApiResponse::ok([
            'general' => ['modificationDate' => $result->modificationDate],
            'treeData' => $result->treeData,
        ]));
    }

    #[Route('/preview-version', name: 'previewversion', methods: ['GET'])]
    public function previewVersionAction(
        Environment $twig,
        PreviewVersionHandler $previewVersion,
        PreviewVersionPayload $payload,
    ): Response
    {
        $result = $previewVersion($payload);

        Tool\UserTimezone::setUserTimezone($payload->userTimezone);
        if ($timezone = Tool\UserTimezone::getUserTimezone()) {
            $twig->getExtension(CoreExtension::class)->setTimezone($timezone);
        }

        return $this->render('@OpenDxpAdmin/admin/data_object/data_object/preview_version.html.twig', [
            'object' => $result->object,
            'versionNote' => $result->version->getNote(),
            'validLanguages' => Tool::getValidLanguages(),
        ]);
    }

    #[Route('/diff-versions/from/{from}/to/{to}', name: 'diffversions', methods: ['GET'])]
    public function diffVersionsAction(
        Environment $twig,
        DiffVersionsHandler $diffVersions,
        DiffVersionsPayload $payload,
    ): Response
    {
        $result = $diffVersions($payload);

        Tool\UserTimezone::setUserTimezone($payload->userTimezone);
        if ($timezone = Tool\UserTimezone::getUserTimezone()) {
            $twig->getExtension(CoreExtension::class)->setTimezone($timezone);
        }

        return $this->render('@OpenDxpAdmin/admin/data_object/data_object/diff_versions.html.twig', [
            'object1' => $result->object1,
            'versionNote1' => $result->version1->getNote(),
            'object2' => $result->object2,
            'versionNote2' => $result->version2->getNote(),
            'validLanguages' => Tool::getValidLanguages(),
        ]);
    }
}
