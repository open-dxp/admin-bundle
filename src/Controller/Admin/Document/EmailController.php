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

use Exception;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 *
 * @internal
 */
#[Route('/email', name: 'opendxp_admin_document_email_')]
class EmailController extends DocumentControllerBase
{
    /**
     * @throws Exception
     */
    #[Route('/get-data-by-id', name: 'getdatabyid', methods: ['GET'])]
    public function getDataByIdAction(Request $request): JsonResponse
    {
        $email = Document\Email::getById((int)$request->get('id'));

        if (!$email) {
            throw $this->createNotFoundException('Email not found');
        }

        if (($lock = $this->checkForLock($email, $request->getSession()->getId())) instanceof JsonResponse) {
            return $lock;
        }

        $email = clone $email;
        $draftVersion = null;
        $email = $this->getLatestVersion($email, $draftVersion);

        $versions = Element\Service::getSafeVersionInfo($email->getVersions());
        $email->setVersions(array_splice($versions, -1, 1));
        $email->setParent(null);

        // unset useless data
        $email->setEditables(null);
        $email->setChildren(null);

        $data = $email->getObjectVars();
        $data['locked'] = $email->isLocked();

        $this->addTranslationsData($email, $data);
        $this->minimizeProperties($email, $data);
        $this->populateUsersNames($email, $data);

        $data['url'] = $email->getUrl();

        return $this->preSendDataActions($data, $email, $draftVersion);
    }

    /**
     * @throws Exception
     */
    #[Route('/save', name: 'save', methods: ['PUT', 'POST'])]
    public function saveAction(Request $request): JsonResponse
    {
        $page = Document\Email::getById((int) $request->get('id'));
        if (!$page) {
            throw $this->createNotFoundException('Email not found');
        }

        [$task, $page, $version] = $this->saveDocument($page, $request);
        $this->saveToSession($page, $request->getSession());

        if ($task === self::TASK_PUBLISH || $task === self::TASK_UNPUBLISH) {
            $treeData = $this->getTreeNodeConfig($page);

            return $this->adminJson([
                'success' => true,
                'data' => [
                    'versionDate' => $page->getModificationDate(),
                    'versionCount' => $page->getVersionCount(),
                ],
                'treeData' => $treeData,
            ]);
        }
        $draftData = [];
        if ($version) {
            $draftData = [
                'id' => $version->getId(),
                'modificationDate' => $version->getDate(),
                'isAutoSave' => $version->isAutoSave(),
            ];
        }

        return $this->adminJson(['success' => true, 'draft' => $draftData]);
    }

    protected function setValuesToDocument(Request $request, Document $document): void
    {
        $this->addSettingsToDocument($request, $document);
        $this->addDataToDocument($request, $document);
        $this->addPropertiesToDocument($request, $document);
        $this->applySchedulerDataToElement($request, $document, $this->getAdminUser());
    }
}
