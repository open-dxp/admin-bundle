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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\ShowEmailLog\GetEmailLogHtml;

use OpenDxp\Bundle\AdminBundle\Payload\Common\IdQueryPayload;
use OpenDxp\Model\Tool\Email\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetEmailLogHtmlHandler
{
    public function __invoke(IdQueryPayload $payload): GetEmailLogHtmlResult
    {
        $log = Log::getById($payload->id);

        if (!$log) {
            throw new NotFoundHttpException(sprintf('Email log with id %d not found', $payload->id));
        }

        return new GetEmailLogHtmlResult(htmlLog: $log->getHtmlLog() ?: null);
    }
}
