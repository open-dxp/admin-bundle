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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\Document;

use Exception;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element;
use OpenDxp\Model\Schedule\Task;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[Route('/link', name: 'opendxp_admin_document_link_')]
class LinkController extends DocumentControllerBase
{
    /**
     * @throws Exception
     */
    #[Route('/get-data-by-id', name: 'getdatabyid', methods: ['GET'])]
    public function getDataByIdAction(Request $request, SerializerInterface $serializer): JsonResponse
    {
        $link = Document\Link::getById((int)$request->query->get('id'));

        if (!$link) {
            throw $this->createNotFoundException('Link not found');
        }

        if (($lock = $this->checkForLock($link, $request->getSession()->getId())) instanceof JsonResponse) {
            return $lock;
        }

        $link = clone $link;

        $link->setElement(null);
        $link->setParent(null);

        $data = $serializer->serialize($link->getObjectVars(), 'json', []);
        $data = json_decode($data, true);
        $data['locked'] = $link->isLocked();
        $data['rawHref'] = $link->getRawHref();
        $data['scheduledTasks'] = array_map(
            static fn (Task $task) => $task->getObjectVars(),
            $link->getScheduledTasks()
        );

        $this->addTranslationsData($link, $data);
        $this->minimizeProperties($link, $data);
        $this->populateUsersNames($link, $data);

        return $this->preSendDataActions($data, $link);
    }

    /**
     * @throws Exception
     */
    #[Route('/save', name: 'save', methods: ['POST', 'PUT'])]
    public function saveAction(Request $request): JsonResponse
    {
        $link = Document\Link::getById((int) $request->request->get('id'));
        if (!$link) {
            throw $this->createNotFoundException('Link not found');
        }

        $result = $this->saveDocument($link, $request);
        /** @var Document\Link $link */
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
     * @param Document\Link $document
     */
    protected function setValuesToDocument(Request $request, Document $document): void
    {
        if ($request->request->has('data')) {
            $data = $this->decodeJson($request->request->get('data'));

            $path = $data['path'];

            if (!empty($path)) {
                $target = null;
                if ($data['linktype'] === 'internal' && $data['internalType']) {
                    $target = Element\Service::getElementByPath($data['internalType'], $path);
                    if ($target) {
                        $data['internal'] = $target->getId();
                    }
                }

                if (!$target) {
                    if ($target = Document::getByPath($path)) {
                        $data['internalType'] = 'document';
                        $data['internal'] = $target->getId();
                    } elseif ($target = Asset::getByPath($path)) {
                        $data['internalType'] = 'asset';
                        $data['internal'] = $target->getId();
                    } elseif ($target = Concrete::getByPath($path)) {
                        $data['internalType'] = 'object';
                        $data['internal'] = $target->getId();
                    } else {
                        $data['linktype'] = 'direct';
                        $data['internalType'] = null;
                        $data['internal'] = null;
                        $data['direct'] = $path;
                    }

                    if ($target) {
                        $data['linktype'] = 'internal';
                        $data['direct'] = '';
                    }
                }
            } else {
                // clear content of link
                $data['linktype'] = 'internal';
                $data['direct'] = '';
                $data['internalType'] = null;
                $data['internal'] = null;
            }

            unset($data['path']);

            $document->setValues($data);
        }

        $this->addPropertiesToDocument($request, $document);
        $this->applySchedulerDataToElement($request, $document, $this->getAdminUser());
    }
}
