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
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Variants\GetVariantsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Variants\UpdateObjectKeyHandler;
use OpenDxp\Bundle\AdminBundle\Security\CsrfProtectionHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/variants', name: 'opendxp_admin_dataobject_variants_')]
class VariantsController extends AdminAbstractController
{
    #[Route('/update-key', name: 'updatekey', methods: ['PUT'])]
    public function updateKeyAction(UpdateObjectKeyHandler $updateObjectKey, Request $request): JsonResponse
    {
        $result = $updateObjectKey(
            $request->request->getInt('id'),
            $request->request->get('key'),
        );

        return $this->adminJson($result->data);
    }

    #[Route('/get-variants', name: 'getvariants', methods: ['POST'])]
    public function getVariantsAction(
        GetVariantsHandler $getVariants,
        Request $request,
        CsrfProtectionHandler $csrfProtection,
    ): JsonResponse {
        $csrfProtection->checkCsrfToken($request);

        $allParams = [...$request->request->all(), ...$request->query->all()];
        $requestedLanguage = $allParams['language'] ?? null;
        if ($requestedLanguage && $requestedLanguage !== 'default') {
            $request->setLocale($requestedLanguage);
        } else {
            $requestedLanguage = $request->getLocale();
        }

        $result = $getVariants(
            (int) $request->request->get('objectId'),
            $allParams,
            $requestedLanguage,
        );

        return $this->adminJson($result->data);
    }
}
