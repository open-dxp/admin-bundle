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
use OpenDxp\Bundle\AdminBundle\DependencyInjection\OpenDxpAdminExtension;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\Element\AddNoteHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\AnalyzePermissionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\DeleteAllVersionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\DeleteDraftHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\DeleteNoteHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\DeleteVersionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\FindUsagesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetNicePathHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetNoteListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetPredefinedPropertiesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetReplaceAssignmentsBatchJobsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetRequiredByDependenciesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetRequiresDependenciesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetSubtypeHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetVersionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\LockElementHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\ReplaceAssignmentsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\TypePathHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockElementHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockElementsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\UnlockPropagateHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\VersionUpdateHandler;
use OpenDxp\Bundle\AdminBundle\Helper\QueryParams;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
class ElementController extends AdminAbstractController
{
    #[Route('/element/lock-element', name: 'opendxp_admin_element_lockelement', methods: ['PUT'])]
    public function lockElementAction(Request $request, LockElementHandler $lockElement): Response
    {
        $lockElement($request->request->getInt('id'), $request->request->get('type'), $request->getSession()->getId());

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/element/unlock-element', name: 'opendxp_admin_element_unlockelement', methods: ['PUT'])]
    public function unlockElementAction(Request $request, UnlockElementHandler $unlockElement): Response
    {
        $unlockElement((int) $request->request->get('id'), $request->request->get('type'));

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/element/unlock-elements', name: 'opendxp_admin_element_unlockelements', methods: ['POST'])]
    public function unlockElementsAction(Request $request, UnlockElementsHandler $unlockElements): Response
    {
        $body = json_decode($request->getContent(), true) ?? [];
        $unlockElements($body['elements'] ?? []);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/element/get-subtype', name: 'opendxp_admin_element_getsubtype', methods: ['GET'])]
    public function getSubtypeAction(
        GetSubtypeHandler $getSubtype,
        #[MapQueryParameter] string $id = '',
        #[MapQueryParameter] ?string $type = null,
    ): JsonResponse
    {
        $result = ($getSubtype)($id, $type);

        return $this->adminJson(ApiResponse::ok(['subtype' => $result->subtype, 'id' => $result->id, 'type' => $result->type]));
    }

    protected function processNoteTypesFromParameters(string $parameterName): JsonResponse
    {
        $config = $this->getParameter($parameterName);
        $result = [];
        foreach ($config as $configEntry) {
            $result[] = [
                'name' => $configEntry,
            ];
        }

        return $this->adminJson(['noteTypes' => $result]);
    }

    #[Route('/element/note-types', name: 'opendxp_admin_element_notetypes', methods: ['GET'])]
    public function noteTypes(
        #[MapQueryParameter] ?string $ctype = null,
    ): JsonResponse
    {
        return match ($ctype) {
            'document' => $this->processNoteTypesFromParameters(OpenDxpAdminExtension::PARAM_DOCUMENTS_NOTES_EVENTS_TYPES),
            'asset' => $this->processNoteTypesFromParameters(OpenDxpAdminExtension::PARAM_ASSETS_NOTES_EVENTS_TYPES),
            'object' => $this->processNoteTypesFromParameters(OpenDxpAdminExtension::PARAM_DATAOBJECTS_NOTES_EVENTS_TYPES),
            default => $this->adminJson(['noteTypes' => []]),
        };
    }

    #[Route('/element/note-list', name: 'opendxp_admin_element_notelist', methods: ['POST'])]
    #[IsGranted(CorePermission::NotesEvents->value)]
    public function noteListAction(
        Request $request,
        GetNoteListHandler $getNoteList,
        DeleteNoteHandler $deleteNote,
        #[MapQueryParameter] ?string $xaction = null,
    ): JsonResponse
    {

        if ($xaction === 'destroy') {
            $data = $this->decodeJson($request->request->get('data'));
            $success = $deleteNote((int) $data['id']);

            return $this->adminJson(ApiResponse::fromBool($success));
        }

        $result = ($getNoteList)(
            offset: $request->request->getInt('start', 0),
            limit: $request->request->getInt('limit') ?: null,
            sortingSettings: QueryParams::extractSortingSettings($request->request->all()),
            filterText: $request->request->get('filterText'),
            filterJson: $request->request->get('filter'),
            cid: $request->request->has('cid') ? $request->request->get('cid') : null,
            ctype: $request->request->has('ctype') ? $request->request->get('ctype') : null,
        );

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    #[Route('/element/note-add', name: 'opendxp_admin_element_noteadd', methods: ['POST'])]
    #[IsGranted(CorePermission::NotesEvents->value)]
    public function noteAddAction(Request $request, AddNoteHandler $addNote): JsonResponse
    {

        ($addNote)(
            cid: (int) $request->request->get('cid'),
            ctype: $request->request->get('ctype'),
            title: $request->request->get('title'),
            description: $request->request->get('description'),
            type: $request->request->get('type'),
        );

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/element/find-usages', name: 'opendxp_admin_element_findusages', methods: ['GET'])]
    public function findUsagesAction(
        FindUsagesHandler $findUsages,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)] ?int $id = null,
        #[MapQueryParameter] ?string $type = null,
        #[MapQueryParameter] ?string $path = null,
        #[MapQueryParameter] int $limit = 50,
        #[MapQueryParameter] int $start = 0,
        #[MapQueryParameter] ?string $sort = null,
    ): JsonResponse
    {
        $result = ($findUsages)(
            id: $id,
            type: $type,
            path: $path,
            limit: $limit,
            offset: $start,
            sort: $sort,
        );

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total, 'hasHidden' => $result->hasHidden]));
    }

    #[Route('/element/get-replace-assignments-batch-jobs', name: 'opendxp_admin_element_getreplaceassignmentsbatchjobs', methods: ['GET'])]
    public function getReplaceAssignmentsBatchJobsAction(
        GetReplaceAssignmentsBatchJobsHandler $getReplaceAssignmentsBatchJobs,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)] ?int $id = null,
        #[MapQueryParameter] ?string $type = null,
        #[MapQueryParameter] ?string $path = null,
    ): JsonResponse
    {
        $jobs = $getReplaceAssignmentsBatchJobs($id, $type, $path);

        return $this->adminJson(ApiResponse::ok(['jobs' => $jobs]));
    }

    #[Route('/element/replace-assignments', name: 'opendxp_admin_element_replaceassignments', methods: ['POST'])]
    public function replaceAssignmentsAction(Request $request, ReplaceAssignmentsHandler $replaceAssignments): JsonResponse
    {
        ($replaceAssignments)(
            type: $request->request->get('type'),
            id: $request->request->getInt('id'),
            sourceType: $request->request->get('sourceType'),
            sourceId: $request->request->getInt('sourceId'),
            targetType: $request->request->get('targetType'),
            targetId: $request->request->getInt('targetId'),
        );

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/element/unlock-propagate', name: 'opendxp_admin_element_unlockpropagate', methods: ['PUT'])]
    public function unlockPropagateAction(Request $request, UnlockPropagateHandler $unlockPropagate): JsonResponse
    {
        $success = $unlockPropagate($request->request->get('type'), $request->request->getInt('id'));

        return $this->adminJson(ApiResponse::fromBool($success));
    }

    #[Route('/element/type-path', name: 'opendxp_admin_element_typepath', methods: ['GET'])]
    public function typePathAction(
        TypePathHandler $typePath,
        #[MapQueryParameter] int $id = 0,
        #[MapQueryParameter] ?string $type = null,
    ): JsonResponse
    {
        $result = ($typePath)($id, $type);

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
    public function versionUpdateAction(Request $request, VersionUpdateHandler $versionUpdate): JsonResponse
    {
        $data = $this->decodeJson($request->request->get('data'));
        ($versionUpdate)($data);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/element/get-nice-path', name: 'opendxp_admin_element_getnicepath', methods: ['POST'])]
    public function getNicePathAction(Request $request, GetNicePathHandler $getNicePath): JsonResponse
    {
        $source = $this->decodeJson($request->request->get('source'));
        $context = $request->request->has('context') ? $this->decodeJson($request->request->get('context')) : [];
        $targets = $this->decodeJson($request->request->get('targets'));

        $result = ($getNicePath)(
            source: $source,
            context: $context,
            targets: $targets,
            loadEditModeData: $request->request->getBoolean('loadEditModeData'),
            idProperty: $request->request->get('idProperty', 'id'),
        );

        return $this->adminJson(ApiResponse::ok(['data' => $result->data]));
    }

    #[Route('/element/get-versions', name: 'opendxp_admin_element_getversions', methods: ['GET'])]
    public function getVersionsAction(
        GetVersionsHandler $getVersions,
        #[MapQueryParameter] int $id = 0,
        #[MapQueryParameter] ?string $elementType = null,
    ): JsonResponse
    {
        $result = ($getVersions)($id, $elementType);

        return $this->adminJson(['versions' => $result->versions]);
    }

    #[Route('/element/delete-draft', name: 'opendxp_admin_element_deletedraft', methods: ['DELETE'])]
    public function deleteDraftAction(Request $request, DeleteDraftHandler $deleteDraft): JsonResponse
    {
        $deleteDraft((int) $request->request->get('id'));

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/element/delete-version', name: 'opendxp_admin_element_deleteversion', methods: ['DELETE'])]
    public function deleteVersionAction(Request $request, DeleteVersionHandler $deleteVersion): JsonResponse
    {
        $deleteVersion((int) $request->request->get('id'));

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/element/delete-all-versions', name: 'opendxp_admin_element_deleteallversion', methods: ['DELETE'])]
    public function deleteAllVersionAction(Request $request, DeleteAllVersionsHandler $deleteAllVersions): JsonResponse
    {
        ($deleteAllVersions)(
            elementId: $request->request->getInt('id'),
            elementModificationdate: $request->request->get('date'),
            elementType: $request->request->get('type'),
        );

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/element/get-requires-dependencies', name: 'opendxp_admin_element_getrequiresdependencies', methods: ['GET'])]
    public function getRequiresDependenciesAction(
        GetRequiresDependenciesHandler $getRequiresDependencies,
        #[MapQueryParameter] int $id = 0,
        #[MapQueryParameter] ?string $elementType = null,
        #[MapQueryParameter] int $start = 0,
        #[MapQueryParameter] int $limit = 25,
        #[MapQueryParameter] ?string $filter = null,
    ): JsonResponse
    {
        $result = ($getRequiresDependencies)(
            id: $id,
            type: $elementType,
            offset: $start,
            limit: $limit,
            filterJson: $filter,
        );

        return $this->adminJson($result->data);
    }

    #[Route('/element/get-required-by-dependencies', name: 'opendxp_admin_element_getrequiredbydependencies', methods: ['GET'])]
    public function getRequiredByDependenciesAction(
        GetRequiredByDependenciesHandler $getRequiredByDependencies,
        #[MapQueryParameter] int $id = 0,
        #[MapQueryParameter] ?string $elementType = null,
        #[MapQueryParameter] int $start = 0,
        #[MapQueryParameter] int $limit = 25,
        #[MapQueryParameter] ?string $filter = null,
    ): JsonResponse
    {
        $result = ($getRequiredByDependencies)(
            id: $id,
            type: $elementType,
            offset: $start,
            limit: $limit,
            filterJson: $filter,
        );

        return $this->adminJson($result->data);
    }

    #[Route('/element/get-predefined-properties', name: 'opendxp_admin_element_getpredefinedproperties', methods: ['GET'])]
    public function getPredefinedPropertiesAction(
        GetPredefinedPropertiesHandler $getPredefinedProperties,
        #[MapQueryParameter] ?string $elementType = null,
        #[MapQueryParameter] ?string $query = null,
    ): JsonResponse
    {
        $result = ($getPredefinedProperties)($elementType, $query);

        return $this->adminJson(['properties' => $result->properties]);
    }

    #[Route('/element/analyze-permissions', name: 'opendxp_admin_element_analyzepermissions', methods: ['POST'])]
    public function analyzePermissionsAction(Request $request, AnalyzePermissionsHandler $analyzePermissions): Response
    {
        $result = ($analyzePermissions)(
            userId: $request->request->getInt('userId') ?: null,
            elementType: $request->request->get('elementType'),
            elementId: $request->request->getInt('elementId'),
        );

        return $this->adminJson(ApiResponse::ok(['data' => $result->data]));
    }
}
