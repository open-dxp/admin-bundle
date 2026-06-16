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
use OpenDxp\Bundle\AdminBundle\Normalizer\ElementResponseNormalizer;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Version;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class PublishVersionHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly ElementResponseNormalizer $normalizer,
    ) {}

    public function __invoke(int $versionId): PublishVersionResult
    {
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        $version = Version::getById($versionId);
        $object = $version?->loadData();

        if (!$object instanceof DataObject\AbstractObject) {
            throw new DataObjectNotFoundException($versionId);
        }

        $currentObject = DataObject::getById($object->getId());
        if (!$currentObject?->isAllowed('publish')) {
            throw new AccessDeniedHttpException('Missing permission to publish object version');
        }

        $object->setPublished(true);
        $object->setUserModification($userId);
        $object->save();

        $treeData = [];
        $this->normalizer->normalize($object, $treeData, self::class);

        return new PublishVersionResult(
            modificationDate: (int) $object->getModificationDate(),
            treeData: $treeData,
        );
    }
}
