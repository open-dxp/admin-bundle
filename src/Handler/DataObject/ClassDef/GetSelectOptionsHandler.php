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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef;

use OpenDxp\Model\DataObject;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetSelectOptionsHandler
{
    public function __invoke(?string $id): GetSelectOptionsResult
    {
        $selectOptions = DataObject\SelectOptions\Config::getById($id);
        if (!$selectOptions instanceof DataObject\SelectOptions\Config) {
            throw new NotFoundHttpException('Not Found', code: 1677133720896);
        }

        $data = $selectOptions->getObjectVars();
        $data['isWriteable'] = $selectOptions->isWriteable();
        $data['enumName'] = $selectOptions->getEnumName(true);

        return new GetSelectOptionsResult(data: $data);
    }
}
