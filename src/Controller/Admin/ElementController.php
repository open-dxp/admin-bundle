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

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\Element\AnalyzePermissions\AnalyzePermissionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\AnalyzePermissions\AnalyzePermissionsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\DeleteAllVersions\DeleteAllVersionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\DeleteAllVersions\DeleteAllVersionsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\DeleteNote\DeleteNoteHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\DeleteVersion\DeleteVersionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\DeleteDraft\DeleteDraftHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\FindUsages\FindUsagesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\FindUsages\FindUsagesPayload;
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
use OpenDxp\Bundle\AdminBundle\Handler\Element\AddNote\AddNoteHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\AddNote\AddNotePayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\LockElement\LockElementHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\LockElement\LockElementPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\ReplaceAssignments\ReplaceAssignmentsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\ReplaceAssignments\ReplaceAssignmentsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\TypePath\TypePathHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\TypePath\TypePathPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockElements\UnlockElementsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockElements\UnlockElementsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockPropagate\UnlockPropagateHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockPropagate\UnlockPropagatePayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockElement\UnlockElementHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockElement\UnlockElementPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\VersionUpdate\VersionUpdateHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\VersionUpdate\VersionUpdatePayload;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdBodyPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetDependenciesPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\NoteListPayload;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
class ElementController extends AdminAbstractController
{
    #[Route('/element/lock-element', name: 'opendxp_admin_element_lockelement', methods: ['PUT'])]
    public function lockElementAction(
        LockElementHandler $handler,
        LockElementPayload $payload,
    ): Response {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/element/unlock-element', name: 'opendxp_admin_element_unlockelement', methods: ['PUT'])]
    public function unlockElementAction(
        UnlockElementHandler $handler,
        UnlockElementPayload $payload,
    ): Response {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/element/unlock-elements', name: 'opendxp_admin_element_unlockelements', methods: ['POST'])]
    public function unlockElementsAction(
        UnlockElementsHandler $handler,
        UnlockElementsPayload $payload,
    ): Response {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/element/get-subtype', name: 'opendxp_admin_element_getsubtype', methods: ['GET'])]
    public function getSubtypeAction(
        GetSubtypeHandler $handler,
        GetSubtypePayload $payload,
    ): JsonResponse {
        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok([
            'subtype' => $result->subtype,
            'id' => $result->id,
            'type' => $result->type,
        ]));
    }

    #[Route('/element/note-types', name: 'opendxp_admin_element_notetypes', methods: ['GET'])]
    public function noteTypesAction(
        GetNoteTypesPayload $payload,
        GetNoteTypesHandler $handler,
    ): JsonResponse {
        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['noteTypes' => $result->noteTypes]));
    }

    #[Route('/element/note-list', name: 'opendxp_admin_element_notelist', methods: ['POST'])]
    #[IsGranted(CorePermission::NotesEvents->value)]
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

        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok([
            'data' => $result->data,
            'total' => $result->total,
        ]));
    }

    #[Route('/element/note-list-destroy', name: 'opendxp_admin_element_notelist_destroy', methods: ['POST'])]
    #[IsGranted(CorePermission::NotesEvents->value)]
    public function noteListDestroyAction(
        NoteListPayload $payload,
        DeleteNoteHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => []]));
    }

    #[Route('/element/note-add', name: 'opendxp_admin_element_noteadd', methods: ['POST'])]
    #[IsGranted(CorePermission::NotesEvents->value)]
    public function noteAddAction(
        AddNoteHandler $handler,
        AddNotePayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/element/find-usages', name: 'opendxp_admin_element_findusages', methods: ['GET'])]
    public function findUsagesAction(
        FindUsagesHandler $handler,
        FindUsagesPayload $payload,
    ): JsonResponse {
        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok([
            'data' => $result->data,
            'total' => $result->total,
            'hasHidden' => $result->hasHidden,
        ]));
    }

    #[Route('/element/get-replace-assignments-batch-jobs', name: 'opendxp_admin_element_getreplaceassignmentsbatchjobs', methods: ['GET'])]
    public function getReplaceAssignmentsBatchJobsAction(
        GetReplaceAssignmentsBatchJobsHandler $handler,
        GetReplaceAssignmentsBatchJobsPayload $payload,
    ): JsonResponse {
        $jobs = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['jobs' => $jobs->jobs]));
    }

    #[Route('/element/replace-assignments', name: 'opendxp_admin_element_replaceassignments', methods: ['POST'])]
    public function replaceAssignmentsAction(
        ReplaceAssignmentsHandler $handler,
        ReplaceAssignmentsPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/element/unlock-propagate', name: 'opendxp_admin_element_unlockpropagate', methods: ['PUT'])]
    public function unlockPropagateAction(
        UnlockPropagateHandler $handler,
        UnlockPropagatePayload $payload,
    ): JsonResponse {
        $result = $handler($payload);

        return $this->adminJson(ApiResponse::fromBool($result->success));
    }

    #[Route('/element/type-path', name: 'opendxp_admin_element_typepath', methods: ['GET'])]
    public function typePathAction(
        TypePathHandler $handler,
        TypePathPayload $payload,
    ): JsonResponse {
        $result = $handler($payload);

        $data = [
            'index' => $result->index,
            'idPath' => $result->idPath,
            'typePath' => $result->typePath,
            'fullpath' => $result->fullpath,
        ];

        if ($result->sortIndexPath !== null) {
            $data['sortIndexPath'] = $result->sortIndexPath;
        }

        return $this->adminJson(ApiResponse::ok($data));
    }

    #[Route('/element/version-update', name: 'opendxp_admin_element_versionupdate', methods: ['PUT'])]
    public function versionUpdateAction(
        VersionUpdateHandler $handler,
        VersionUpdatePayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/element/get-nice-path', name: 'opendxp_admin_element_getnicepath', methods: ['POST'])]
    public function getNicePathAction(
        GetNicePathHandler $handler,
        GetNicePathPayload $payload,
    ): JsonResponse {
        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data]));
    }

    #[Route('/element/get-versions', name: 'opendxp_admin_element_getversions', methods: ['GET'])]
    public function getVersionsAction(
        GetVersionsHandler $handler,
        GetVersionsPayload $payload,
    ): JsonResponse {
        $result = $handler($payload);

        return $this->adminJson(['versions' => $result->versions]);
    }

    #[Route('/element/delete-draft', name: 'opendxp_admin_element_deletedraft', methods: ['DELETE'])]
    public function deleteDraftAction(
        DeleteDraftHandler $handler,
        IdBodyPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/element/delete-version', name: 'opendxp_admin_element_deleteversion', methods: ['DELETE'])]
    public function deleteVersionAction(
        DeleteVersionHandler $handler,
        IdBodyPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/element/delete-all-versions', name: 'opendxp_admin_element_deleteallversion', methods: ['DELETE'])]
    public function deleteAllVersionAction(
        DeleteAllVersionsHandler $handler,
        DeleteAllVersionsPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/element/get-requires-dependencies', name: 'opendxp_admin_element_getrequiresdependencies', methods: ['GET'])]
    public function getRequiresDependenciesAction(
        GetRequiresDependenciesHandler $handler,
        GetDependenciesPayload $payload,
    ): JsonResponse {
        $result = $handler($payload);

        return $this->adminJson($result->data);
    }

    #[Route('/element/get-required-by-dependencies', name: 'opendxp_admin_element_getrequiredbydependencies', methods: ['GET'])]
    public function getRequiredByDependenciesAction(
        GetRequiredByDependenciesHandler $handler,
        GetDependenciesPayload $payload,
    ): JsonResponse {
        $result = $handler($payload);

        return $this->adminJson($result->data);
    }

    #[Route('/element/get-predefined-properties', name: 'opendxp_admin_element_getpredefinedproperties', methods: ['GET'])]
    public function getPredefinedPropertiesAction(
        GetPredefinedPropertiesHandler $handler,
        GetPredefinedPropertiesPayload $payload,
    ): JsonResponse {
        $result = $handler($payload);

        return $this->adminJson(['properties' => $result->properties]);
    }

    #[Route('/element/analyze-permissions', name: 'opendxp_admin_element_analyzepermissions', methods: ['POST'])]
    public function analyzePermissionsAction(
        AnalyzePermissionsHandler $handler,
        AnalyzePermissionsPayload $payload,
    ): Response {
        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data]));
    }
}
