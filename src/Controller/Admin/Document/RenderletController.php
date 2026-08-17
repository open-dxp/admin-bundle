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

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Renderlet\RenderRenderlet\RenderRenderletHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Renderlet\RenderRenderlet\RenderRenderletPayload;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
class RenderletController extends AdminAbstractController
{
    /**
     * Handles editmode preview for renderlets
     */
    #[Route('/document_tag/renderlet', name: 'opendxp_admin_document_renderlet_renderlet', methods: ['GET'])]
    public function renderletAction(
        RenderRenderletPayload $payload,
        RenderRenderletHandler $handler,
    ): Response {
        $result = $handler($payload);

        return new Response($result->html);
    }
}
