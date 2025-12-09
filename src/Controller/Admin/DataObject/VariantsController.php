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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\DataObject;

use Exception;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Helper\GridHelperService;
use OpenDxp\Bundle\AdminBundle\Security\CsrfProtectionHandler;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Model\DataObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Route('/variants', name: 'opendxp_admin_dataobject_variants_')]
class VariantsController extends AdminAbstractController
{
    use DataObjectActionsTrait;

    #[Route('/update-key', name: 'updatekey', methods: ['PUT'])]
    public function updateKeyAction(Request $request): JsonResponse
    {
        $id = $request->request->getInt('id');
        $key = $request->request->get('key');
        $object = DataObject\Concrete::getById($id);

        return $this->adminJson($this->renameObject($object, $key));
    }

    /**
     * @throws Exception
     */
    #[Route('/get-variants', name: 'getvariants', methods: ['GET', 'POST'])]
    public function getVariantsAction(
        Request $request,
        EventDispatcherInterface $eventDispatcher,
        GridHelperService $gridHelperService,
        LocaleServiceInterface $localeService,
        CsrfProtectionHandler $csrfProtection
    ): JsonResponse {
        $parentObject = DataObject\Concrete::getById((int) $request->get('objectId'));
        if (empty($parentObject)) {
            throw new Exception('No Object found with id ' . $request->get('objectId'));
        }

        if (!$parentObject->isAllowed('view')) {
            throw new Exception('Permission denied');
        }

        $allParams = [...$request->request->all(), ...$request->query->all()];
        $allParams['folderId'] = $parentObject->getId();
        $allParams['classId'] = $parentObject->getClassId();

        $csrfProtection->checkCsrfToken($request);

        $result = $this->gridProxy(
            $allParams,
            DataObject::OBJECT_TYPE_VARIANT,
            $request,
            $eventDispatcher,
            $gridHelperService,
            $localeService
        );

        return $this->adminJson($result);
    }
}
