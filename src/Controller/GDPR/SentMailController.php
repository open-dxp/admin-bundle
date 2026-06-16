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
use OpenDxp\Bundle\AdminBundle\Security\Permission\AdminPermission;
use OpenDxp\Model\Tool\Email\Log;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
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
    public function exportDataObjectAction(
        #[MapQueryParameter] int $id = 0,
    ): JsonResponse
    {
        $this->checkPermission('emails');

        $sentMail = Log::getById($id);
        if (!$sentMail) {
            throw $this->createNotFoundException();
        }

        $sentMailArray = (array)$sentMail;
        $sentMailArray['htmlBody'] = $sentMail->getHtmlLog();
        $sentMailArray['textBody'] = $sentMail->getTextLog();

        $json = $this->encodeJson($sentMailArray, [], JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_PRETTY_PRINT);

        return new JsonResponse($json, 200, [
            'Content-Disposition' => 'attachment; filename="export-mail-' . $sentMail->getId() . '.json"',
        ], true);
    }
}
