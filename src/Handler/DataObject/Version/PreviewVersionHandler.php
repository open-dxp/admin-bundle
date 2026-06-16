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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Version;

use OpenDxp\Bundle\AdminBundle\Exception\DataObject\DataObjectNotFoundException;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Version;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class PreviewVersionHandler
{
    public function __invoke(int $versionId): PreviewVersionResult
    {
        DataObject::setDoNotRestoreKeyAndPath(true);

        $version = Version::getById($versionId);
        $object = $version?->loadData();

        if (!$object instanceof DataObject\AbstractObject) {
            DataObject::setDoNotRestoreKeyAndPath(false);
            throw new DataObjectNotFoundException($versionId);
        }

        if (method_exists($object, 'getLocalizedFields')) {
            /** @var DataObject\Localizedfield $localizedFields */
            $localizedFields = $object->getLocalizedFields();
            $localizedFields->setLoadedAllLazyData();
        }

        DataObject::setDoNotRestoreKeyAndPath(false);

        if (!$object->isAllowed('versions')) {
            throw new AccessDeniedHttpException('Permission denied for version id [' . $versionId . ']');
        }

        return new PreviewVersionResult($object, $version);
    }
}
