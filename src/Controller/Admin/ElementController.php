<?php
declare(strict_types=1);

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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin;

use OpenDxp\Bundle\AdminBundle\Attribute\SessionIdentityAware;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Handler\Element\AddNote\AddNoteHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\AddNote\AddNotePayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\AnalyzePermissions\AnalyzePermissionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\AnalyzePermissions\AnalyzePermissionsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\DeleteAllVersions\DeleteAllVersionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\DeleteAllVersions\DeleteAllVersionsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\DeleteDraft\DeleteDraftHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\DeleteNote\DeleteNoteHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\DeleteVersion\DeleteVersionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\FindUsages\FindUsagesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\FindUsages\FindUsagesPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetDependenciesPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetNicePath\GetNicePathHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetNicePath\GetNicePathPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetNoteList\GetNoteListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetNoteTypes\GetNoteTypesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetNoteTypes\GetNoteTypesPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetPredefinedProperties\GetPredefinedPropertiesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetPredefinedProperties\GetPredefinedPropertiesPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetReplaceAssignmentsBatchJobs\GetReplaceAssignmentsBatchJobsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetReplaceAssignmentsBatchJobs\GetReplaceAssignmentsBatchJobsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetRequiredByDependencies\GetRequiredByDependenciesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetRequiresDependencies\GetRequiresDependenciesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetSubtype\GetSubtypeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetSubtype\GetSubtypePayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetVersions\GetVersionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetVersions\GetVersionsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\LockElement\LockElementHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\LockElement\LockElementPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\NoteListPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\ReplaceAssignments\ReplaceAssignmentsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\ReplaceAssignments\ReplaceAssignmentsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\TypePath\TypePathHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\TypePath\TypePathPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockElement\UnlockElementHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockElement\UnlockElementPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockElements\UnlockElementsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockElements\UnlockElementsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockPropagate\UnlockPropagateHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockPropagate\UnlockPropagatePayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\VersionUpdate\VersionUpdateHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\VersionUpdate\VersionUpdatePayload;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdBodyPayload;
use OpenDxp\Security\CorePermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * @internal
 */
class ElementController extends AdminAbstractController
{
    #[Route('/element/lock-element', name: 'opendxp_admin_element_lockelement', methods: ['PUT'])]
    #[SessionIdentityAware]
    public function lockElementAction(
        LockElementHandler $handler,
        LockElementPayload $payload,
    ): Response {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/element/unlock-element', name: 'opendxp_admin_element_unlockelement', methods: ['PUT'])]
    public function unlockElementAction(
        UnlockElementHandler $handler,
        UnlockElementPayload $payload,
    ): Response {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/element/unlock-elements', name: 'opendxp_admin_element_unlockelements', methods: ['POST'])]
    public function unlockElementsAction(
        UnlockElementsHandler $handler,
        UnlockElementsPayload $payload,
    ): Response {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/element/get-subtype', name: 'opendxp_admin_element_getsubtype', methods: ['GET'])]
    public function getSubtypeAction(
        GetSubtypeHandler $handler,
        GetSubtypePayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/element/note-types', name: 'opendxp_admin_element_notetypes', methods: ['GET'])]
    public function noteTypesAction(
        GetNoteTypesPayload $payload,
        GetNoteTypesHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload), envelope: false);
    }

    #[IsGranted(CorePermission::NotesEvents->value)]
    #[Route('/element/note-list', name: 'opendxp_admin_element_notelist', methods: ['POST'])]
    public function noteListAction(
        Request $request,
        NoteListPayload $payload,
        GetNoteListHandler $handler,
        #[MapQueryParameter] ?string $xaction = null,
    ): Response {
        if ($payload->hasData) {
            return match ($xaction) {
                'destroy' => $this->forward(self::class . '::noteListDestroyAction', [], $request->query->all()),
                default   => throw new BadRequestHttpException(),
            };
        }

        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::NotesEvents->value)]
    #[Route('/element/note-list-destroy', name: 'opendxp_admin_element_notelist_destroy', methods: ['POST'])]
    public function noteListDestroyAction(
        NoteListPayload $payload,
        DeleteNoteHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[IsGranted(CorePermission::NotesEvents->value)]
    #[Route('/element/note-add', name: 'opendxp_admin_element_noteadd', methods: ['POST'])]
    public function noteAddAction(
        AddNoteHandler $handler,
        AddNotePayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/element/find-usages', name: 'opendxp_admin_element_findusages', methods: ['GET'])]
    public function findUsagesAction(
        FindUsagesHandler $handler,
        FindUsagesPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/element/get-replace-assignments-batch-jobs', name: 'opendxp_admin_element_getreplaceassignmentsbatchjobs', methods: ['GET'])]
    public function getReplaceAssignmentsBatchJobsAction(
        GetReplaceAssignmentsBatchJobsHandler $handler,
        GetReplaceAssignmentsBatchJobsPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/element/replace-assignments', name: 'opendxp_admin_element_replaceassignments', methods: ['POST'])]
    public function replaceAssignmentsAction(
        ReplaceAssignmentsHandler $handler,
        ReplaceAssignmentsPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/element/unlock-propagate', name: 'opendxp_admin_element_unlockpropagate', methods: ['PUT'])]
    public function unlockPropagateAction(
        UnlockPropagateHandler $handler,
        UnlockPropagatePayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/element/type-path', name: 'opendxp_admin_element_typepath', methods: ['GET'])]
    public function typePathAction(
        TypePathHandler $handler,
        TypePathPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload), context: [AbstractObjectNormalizer::SKIP_NULL_VALUES => true]);
    }

    #[Route('/element/version-update', name: 'opendxp_admin_element_versionupdate', methods: ['PUT'])]
    public function versionUpdateAction(
        VersionUpdateHandler $handler,
        VersionUpdatePayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/element/get-nice-path', name: 'opendxp_admin_element_getnicepath', methods: ['POST'])]
    public function getNicePathAction(
        GetNicePathHandler $handler,
        GetNicePathPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/element/get-versions', name: 'opendxp_admin_element_getversions', methods: ['GET'])]
    public function getVersionsAction(
        GetVersionsHandler $handler,
        GetVersionsPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload), envelope: false);
    }

    #[Route('/element/delete-draft', name: 'opendxp_admin_element_deletedraft', methods: ['DELETE'])]
    public function deleteDraftAction(
        DeleteDraftHandler $handler,
        IdBodyPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/element/delete-version', name: 'opendxp_admin_element_deleteversion', methods: ['DELETE'])]
    public function deleteVersionAction(
        DeleteVersionHandler $handler,
        IdBodyPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/element/delete-all-versions', name: 'opendxp_admin_element_deleteallversion', methods: ['DELETE'])]
    public function deleteAllVersionAction(
        DeleteAllVersionsHandler $handler,
        DeleteAllVersionsPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/element/get-requires-dependencies', name: 'opendxp_admin_element_getrequiresdependencies', methods: ['GET'])]
    public function getRequiresDependenciesAction(
        GetRequiresDependenciesHandler $handler,
        GetDependenciesPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload), rootProperty: 'data');
    }

    #[Route('/element/get-required-by-dependencies', name: 'opendxp_admin_element_getrequiredbydependencies', methods: ['GET'])]
    public function getRequiredByDependenciesAction(
        GetRequiredByDependenciesHandler $handler,
        GetDependenciesPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload), rootProperty: 'data');
    }

    #[Route('/element/get-predefined-properties', name: 'opendxp_admin_element_getpredefinedproperties', methods: ['GET'])]
    public function getPredefinedPropertiesAction(
        GetPredefinedPropertiesHandler $handler,
        GetPredefinedPropertiesPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload), envelope: false);
    }

    #[Route('/element/analyze-permissions', name: 'opendxp_admin_element_analyzepermissions', methods: ['POST'])]
    public function analyzePermissionsAction(
        AnalyzePermissionsHandler $handler,
        AnalyzePermissionsPayload $payload,
    ): Response {
        return $this->apiJson($handler($payload));
    }
}
