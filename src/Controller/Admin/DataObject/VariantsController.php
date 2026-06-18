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
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Variants\GetVariants\GetVariantsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Variants\GetVariants\GetVariantsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Variants\UpdateObjectKey\UpdateObjectKeyHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Variants\UpdateObjectKey\UpdateObjectKeyPayload;
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
    public function updateKeyAction(UpdateObjectKeyHandler $updateObjectKey, UpdateObjectKeyPayload $payload): JsonResponse
    {
        $result = $updateObjectKey($payload);

        return $this->adminJson($result->data);
    }

    #[Route('/get-variants', name: 'getvariants', methods: ['POST'])]
    public function getVariantsAction(
        GetVariantsHandler $getVariants,
        GetVariantsPayload $payload,
        Request $request,
        CsrfProtectionHandler $csrfProtection,
    ): JsonResponse {
        $csrfProtection->checkCsrfToken($request);

        if ($payload->requestedLanguage !== $request->getLocale()) {
            $request->setLocale($payload->requestedLanguage);
        }

        $result = $getVariants($payload);

        return $this->adminJson($result->data);
    }
}
