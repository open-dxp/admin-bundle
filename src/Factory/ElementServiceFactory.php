<?php

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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Factory;

use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Document;

final class ElementServiceFactory
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function createAssetService(): Asset\Service
    {
        return new Asset\Service($this->userContext->getAdminUser());
    }

    public function createDataObjectService(): DataObject\Service
    {
        return new DataObject\Service($this->userContext->getAdminUser());
    }

    public function createDocumentService(): Document\Service
    {
        return new Document\Service($this->userContext->getAdminUser());
    }
}
