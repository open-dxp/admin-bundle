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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Version\DiffVersions;

use OpenDxp\Bundle\AdminBundle\Exception\DataObject\DataObjectNotFoundException;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Version;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class DiffVersionsHandler
{
    public function __invoke(DiffVersionsPayload $payload): DiffVersionsResult
    {
        DataObject::setDoNotRestoreKeyAndPath(true);

        $version1 = Version::getById($payload->from);
        $object1 = $version1?->loadData();

        if (!$object1 instanceof DataObject\AbstractObject) {
            DataObject::setDoNotRestoreKeyAndPath(false);

            throw new DataObjectNotFoundException($payload->from);
        }

        if (method_exists($object1, 'getLocalizedFields')) {
            /** @var DataObject\Localizedfield $localizedFields1 */
            $localizedFields1 = $object1->getLocalizedFields();
            $localizedFields1->setLoadedAllLazyData();
        }

        $version2 = Version::getById($payload->to);
        $object2 = $version2?->loadData();

        if (!$object2 instanceof DataObject\AbstractObject) {
            DataObject::setDoNotRestoreKeyAndPath(false);

            throw new DataObjectNotFoundException($payload->to);
        }

        if (method_exists($object2, 'getLocalizedFields')) {
            /** @var DataObject\Localizedfield $localizedFields2 */
            $localizedFields2 = $object2->getLocalizedFields();
            $localizedFields2->setLoadedAllLazyData();
        }

        DataObject::setDoNotRestoreKeyAndPath(false);

        if (!$object1->isAllowed('versions') || !$object2->isAllowed('versions')) {
            throw new AccessDeniedHttpException('Permission denied for version ids [' . $payload->from . ', ' . $payload->to . ']');
        }

        return new DiffVersionsResult($object1, $version1, $object2, $version2);
    }
}
