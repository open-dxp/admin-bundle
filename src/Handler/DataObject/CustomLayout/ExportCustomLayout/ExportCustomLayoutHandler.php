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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\ExportCustomLayout;

use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\ExportCustomLayout\ExportCustomLayoutPayload;
use OpenDxp\Logger;
use OpenDxp\Model\DataObject;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ExportCustomLayoutHandler
{
    public function __invoke(ExportCustomLayoutPayload $payload): ExportCustomLayoutResult
    {
        if ($payload->id) {
            $customLayout = DataObject\ClassDefinition\CustomLayout::getById($payload->id);
            if ($customLayout) {
                return new ExportCustomLayoutResult(
                    $customLayout->getName(),
                    DataObject\ClassDefinition\Service::generateCustomLayoutJson($customLayout),
                );
            }
        }

        $errorMessage = ': Custom Layout with id [ ' . $payload->id . ' not found. ]';
        Logger::error($errorMessage);

        throw new NotFoundHttpException($errorMessage);
    }
}
