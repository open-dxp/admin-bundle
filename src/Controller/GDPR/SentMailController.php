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

namespace OpenDxp\Bundle\AdminBundle\Controller\GDPR;

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Handler\GDPR\SentMail\ExportSentMail\ExportSentMailHandler;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdQueryPayload;
use OpenDxp\Bundle\AdminBundle\Security\AdminPermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/sent-mail')]
#[IsGranted(AdminPermission::GdprDataExtractor->value)]
class SentMailController extends AdminAbstractController
{
    #[Route('/export', name: 'opendxp_admin_gdpr_sentmail_exportdataobject', methods: ['GET'])]
    public function exportDataObjectAction(ExportSentMailHandler $handler, IdQueryPayload $payload): JsonResponse
    {
        $this->checkPermission('emails');
        $result = $handler($payload);

        $json = $this->encodeJson($result->data, [], JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_PRETTY_PRINT);

        return new JsonResponse($json, 200, [
            'Content-Disposition' => 'attachment; filename="export-mail-' . $result->mailId . '.json"',
        ], true);
    }
}
