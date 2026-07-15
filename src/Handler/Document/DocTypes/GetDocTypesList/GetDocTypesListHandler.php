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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\DocTypes\GetDocTypesList;

use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\Document\DocType;

final class GetDocTypesListHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(): GetDocTypesListResult
    {
        $adminUser = $this->userContext->getAdminUser();
        $list = new DocType\Listing();

        $docTypes = [];
        foreach ($list->getDocTypes() as $type) {
            if ($adminUser->isAllowed($type->getId(), 'docType')) {
                $data = $type->getObjectVars();
                $data['writeable'] = $type->isWriteable();
                $docTypes[] = $data;
            }
        }

        return new GetDocTypesListResult($docTypes, count($docTypes));
    }
}
