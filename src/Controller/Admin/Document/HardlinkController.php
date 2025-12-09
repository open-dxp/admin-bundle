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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\Document;

use OpenDxp\Model\Document;
use OpenDxp\Model\Schedule\Task;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 *
 * @internal
 */
#[Route('/hardlink', name: 'opendxp_admin_document_hardlink_')]
class HardlinkController extends DocumentControllerBase
{
    /**
     * @throws \Exception
     */
    #[Route('/get-data-by-id', name: 'getdatabyid', methods: ['GET'])]
    public function getDataByIdAction(Request $request): JsonResponse
    {
        $link = Document\Hardlink::getById((int)$request->get('id'));

        if (!$link) {
            throw $this->createNotFoundException('Hardlink not found');
        }

        if (($lock = $this->checkForLock($link, $request->getSession()->getId())) instanceof JsonResponse) {
            return $lock;
        }

        $link = clone $link;
        $link->setParent(null);

        $data = $link->getObjectVars();
        $data['locked'] = $link->isLocked();
        $data['scheduledTasks'] = array_map(
            static function (Task $task) {
                return $task->getObjectVars();
            },
            $link->getScheduledTasks()
        );

        $this->addTranslationsData($link, $data);
        $this->minimizeProperties($link, $data);
        $this->populateUsersNames($link, $data);

        if ($link->getSourceDocument()) {
            $data['sourcePath'] = $link->getSourceDocument()->getRealFullPath();
        }

        return $this->preSendDataActions($data, $link);
    }

    /**
     * @throws \Exception
     */
    #[Route('/save', name: 'save', methods: ['POST', 'PUT'])]
    public function saveAction(Request $request): JsonResponse
    {
        $link = Document\Hardlink::getById((int) $request->get('id'));
        if (!$link) {
            throw $this->createNotFoundException('Hardlink not found');
        }

        $result = $this->saveDocument($link, $request);
        /** @var Document\Hardlink $link */
        $link = $result[1];
        $treeData = $this->getTreeNodeConfig($link);

        return $this->adminJson([
            'success' => true,
            'data' => [
                'versionDate' => $link->getModificationDate(),
                'versionCount' => $link->getVersionCount(),
            ],
            'treeData' => $treeData,
        ]);
    }

    /**
     * @param Document\Hardlink $document
     */
    protected function setValuesToDocument(Request $request, Document $document): void
    {
        // data
        if ($request->get('data')) {
            $data = $this->decodeJson($request->get('data'));

            $sourceId = null;
            if ($sourceDocument = Document::getByPath($data['sourcePath'])) {
                $sourceId = $sourceDocument->getId();
            }
            $document->setSourceId($sourceId);
            $document->setValues($data);
        }

        $this->addPropertiesToDocument($request, $document);
        $this->applySchedulerDataToElement($request, $document, $this->getAdminUser());
    }
}
