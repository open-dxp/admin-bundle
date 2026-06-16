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

namespace OpenDxp\Bundle\AdminBundle\Handler\Email;

use OpenDxp\Model\Tool\Email\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetEmailLogHandler
{
    public function __invoke(int $id): GetEmailLogResult
    {
        $log = Log::getById($id);

        if (!$log) {
            throw new NotFoundHttpException(sprintf('Email log with id %d not found', $id));
        }

        return new GetEmailLogResult(
            textLog: $log->getTextLog(),
            htmlLog: $log->getHtmlLog(),
            objectVars: $log->getObjectVars(),
        );
    }
}
