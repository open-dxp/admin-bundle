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
    #[Route('/lock-element', name: 'opendxp_admin_element_lockelement', methods: ['PUT'])]
    public function lockElementAction(
        LockElementHandler $lockElement,
        LockElementPayload $payload,
    ): Response {
        $lockElement($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/unlock-element', name: 'opendxp_admin_element_unlockelement', methods: ['PUT'])]
    public function unlockElementAction(
        UnlockElementHandler $unlockElement,
        UnlockElementPayload $payload,
    ): Response {
        $unlockElement($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/unlock-elements', name: 'opendxp_admin_element_unlockelements', methods: ['POST'])]
    public function unlockElementsAction(
        UnlockElementsHandler $unlockElements,
        UnlockElementsPayload $payload,
    ): Response {
        $unlockElements($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/get-subtype', name: 'opendxp_admin_element_getsubtype', methods: ['GET'])]
    public function getSubtypeAction(
        GetSubtypeHandler $getSubtype,
        GetSubtypePayload $payload,
    ): JsonResponse {
        $result = $getSubtype($payload);

        return $this->adminJson(ApiResponse::ok([
            'subtype' => $result->subtype,
            'id' => $result->id,
            'type' => $result->type,
        ]));
    }

    #[Route('/note-types', name: 'opendxp_admin_element_notetypes', methods: ['GET'])]
    public function noteTypesAction(
        GetNoteTypesPayload $payload,
        GetNoteTypesHandler $getNoteTypes,
    ): JsonResponse {
        $result = $getNoteTypes($payload);

        return $this->adminJson(ApiResponse::ok(['noteTypes' => $result->noteTypes]));
    }

    #[Route('/note-list', name: 'opendxp_admin_element_notelist', methods: ['POST'])]
    #[IsGranted(CorePermission::NotesEvents->value)]
    public function noteListAction(
        NoteListPayload $payload,
        GetNoteListHandler $getNoteList,
        DeleteNoteHandler $deleteNote,
        #[MapQueryParameter] ?string $xaction = null,
    ): JsonResponse {
        if ($payload->hasData) {
            return match ($xaction) {
                'destroy' => $this->handleDeleteNote($deleteNote, $payload),
                default => throw new BadRequestHttpException(),
            };
        }

        $result = $getNoteList($payload);

        return $this->adminJson(ApiResponse::ok([
            'data' => $result->data,
            'total' => $result->total,
        ]));
    }

    private function handleDeleteNote(DeleteNoteHandler $handler, NoteListPayload $payload): JsonResponse
    {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => []]));
    }

    #[Route('/note-add', name: 'opendxp_admin_element_noteadd', methods: ['POST'])]
    #[IsGranted(CorePermission::NotesEvents->value)]
    public function noteAddAction(
        AddNoteHandler $addNote,
        AddNotePayload $payload,
    ): JsonResponse {
        $addNote($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/find-usages', name: 'opendxp_admin_element_findusages', methods: ['GET'])]
    public function findUsagesAction(
        FindUsagesHandler $findUsages,
        FindUsagesPayload $payload,
    ): JsonResponse {
        $result = $findUsages($payload);

        return $this->adminJson(ApiResponse::ok([
            'data' => $result->data,
            'total' => $result->total,
            'hasHidden' => $result->hasHidden,
        ]));
    }

    #[Route('/get-replace-assignments-batch-jobs', name: 'opendxp_admin_element_getreplaceassignmentsbatchjobs', methods: ['GET'])]
    public function getReplaceAssignmentsBatchJobsAction(
        GetReplaceAssignmentsBatchJobsHandler $getReplaceAssignmentsBatchJobs,
        GetReplaceAssignmentsBatchJobsPayload $payload,
    ): JsonResponse {
        $jobs = $getReplaceAssignmentsBatchJobs($payload);

        return $this->adminJson(ApiResponse::ok(['jobs' => $jobs->jobs]));
    }

    #[Route('/replace-assignments', name: 'opendxp_admin_element_replaceassignments', methods: ['POST'])]
    public function replaceAssignmentsAction(
        ReplaceAssignmentsHandler $replaceAssignments,
        ReplaceAssignmentsPayload $payload,
    ): JsonResponse {
        $replaceAssignments($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/unlock-propagate', name: 'opendxp_admin_element_unlockpropagate', methods: ['PUT'])]
    public function unlockPropagateAction(
        UnlockPropagateHandler $unlockPropagate,
        UnlockPropagatePayload $payload,
    ): JsonResponse {
        $result = $unlockPropagate($payload);

        return $this->adminJson(ApiResponse::fromBool($result->success));
    }

    #[Route('/type-path', name: 'opendxp_admin_element_typepath', methods: ['GET'])]
    public function typePathAction(
        TypePathHandler $typePath,
        TypePathPayload $payload,
    ): JsonResponse {
        $result = $typePath($payload);

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

    #[Route('/version-update', name: 'opendxp_admin_element_versionupdate', methods: ['PUT'])]
    public function versionUpdateAction(
        VersionUpdateHandler $versionUpdate,
        VersionUpdatePayload $payload,
    ): JsonResponse {
        $versionUpdate($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/get-nice-path', name: 'opendxp_admin_element_getnicepath', methods: ['POST'])]
    public function getNicePathAction(
        GetNicePathHandler $getNicePath,
        GetNicePathPayload $payload,
    ): JsonResponse {
        $result = $getNicePath($payload);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data]));
    }

    #[Route('/get-versions', name: 'opendxp_admin_element_getversions', methods: ['GET'])]
    public function getVersionsAction(
        GetVersionsHandler $getVersions,
        GetVersionsPayload $payload,
    ): JsonResponse {
        $result = $getVersions($payload);

        return $this->adminJson(['versions' => $result->versions]);
    }

    #[Route('/delete-draft', name: 'opendxp_admin_element_deletedraft', methods: ['DELETE'])]
    public function deleteDraftAction(
        DeleteDraftHandler $deleteDraft,
        IdBodyPayload $payload,
    ): JsonResponse {
        $deleteDraft($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/delete-version', name: 'opendxp_admin_element_deleteversion', methods: ['DELETE'])]
    public function deleteVersionAction(
        DeleteVersionHandler $deleteVersion,
        IdBodyPayload $payload,
    ): JsonResponse {
        $deleteVersion($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/delete-all-versions', name: 'opendxp_admin_element_deleteallversion', methods: ['DELETE'])]
    public function deleteAllVersionAction(
        DeleteAllVersionsHandler $deleteAllVersions,
        DeleteAllVersionsPayload $payload,
    ): JsonResponse {
        $deleteAllVersions($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/get-requires-dependencies', name: 'opendxp_admin_element_getrequiresdependencies', methods: ['GET'])]
    public function getRequiresDependenciesAction(
        GetRequiresDependenciesHandler $getRequiresDependencies,
        GetDependenciesPayload $payload,
    ): JsonResponse {
        $result = $getRequiresDependencies($payload);

        return $this->adminJson($result->data);
    }

    #[Route('/get-required-by-dependencies', name: 'opendxp_admin_element_getrequiredbydependencies', methods: ['GET'])]
    public function getRequiredByDependenciesAction(
        GetRequiredByDependenciesHandler $getRequiredByDependencies,
        GetDependenciesPayload $payload,
    ): JsonResponse {
        $result = $getRequiredByDependencies($payload);

        return $this->adminJson($result->data);
    }

    #[Route('/get-predefined-properties', name: 'opendxp_admin_element_getpredefinedproperties', methods: ['GET'])]
    public function getPredefinedPropertiesAction(
        GetPredefinedPropertiesHandler $getPredefinedProperties,
        GetPredefinedPropertiesPayload $payload,
    ): JsonResponse {
        $result = $getPredefinedProperties($payload);

        return $this->adminJson(['properties' => $result->properties]);
    }

    #[Route('/analyze-permissions', name: 'opendxp_admin_element_analyzepermissions', methods: ['POST'])]
    public function analyzePermissionsAction(
        AnalyzePermissionsHandler $analyzePermissions,
        AnalyzePermissionsPayload $payload,
    ): Response {
        $result = $analyzePermissions($payload);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data]));
    }
}
