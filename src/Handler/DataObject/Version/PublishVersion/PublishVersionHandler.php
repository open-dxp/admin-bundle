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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Version\PublishVersion;

use OpenDxp\Bundle\AdminBundle\Enricher\Element\AdminStyleEnricher;
use OpenDxp\Bundle\AdminBundle\Exception\DataObject\DataObjectNotFoundException;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdBodyPayload;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Version;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class PublishVersionHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly AdminStyleEnricher $adminStyleEnricher,
    ) {}

    public function __invoke(IdBodyPayload $payload): PublishVersionResult
    {
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        $version = Version::getById($payload->id);
        $object = $version?->loadData();

        if (!$object instanceof DataObject\Concrete) {
            throw new DataObjectNotFoundException($payload->id);
        }

        $currentObject = DataObject::getById($object->getId());
        if (!$currentObject?->isAllowed('publish')) {
            throw new AccessDeniedHttpException('Missing permission to publish object version');
        }

        $object->setPublished(true);
        $object->setUserModification($userId);
        $object->save();

        $treeData = [];
        $this->adminStyleEnricher->forTree($object, $treeData);

        return new PublishVersionResult(
            modificationDate: (int) $object->getModificationDate(),
            treeData: $treeData,
        );
    }
}
