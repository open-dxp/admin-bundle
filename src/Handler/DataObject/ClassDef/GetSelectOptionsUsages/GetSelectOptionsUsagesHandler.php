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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetSelectOptionsUsages;

use OpenDxp\Model\DataObject;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetSelectOptionsUsagesHandler
{
    public function __invoke(GetSelectOptionsUsagesPayload $payload): GetSelectOptionsUsagesResult
    {
        $selectOptions = DataObject\SelectOptions\Config::getById($payload->id);
        if (!$selectOptions instanceof DataObject\SelectOptions\Config) {
            throw new NotFoundHttpException('Not Found', code: 1677133720896);
        }

        $usages = [];
        foreach ($selectOptions->getFieldsUsedIn() as $className => $fieldNames) {
            foreach ($fieldNames as $fieldName) {
                $usages[] = [
                    'class' => $className,
                    'field' => $fieldName,
                ];
            }
        }

        return new GetSelectOptionsUsagesResult(usages: $usages);
    }
}
