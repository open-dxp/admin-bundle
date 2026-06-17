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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Folder\SaveFolder;

use OpenDxp\Bundle\AdminBundle\Handler\Document\Folder\SaveFolder\SaveFolderPayload;
use OpenDxp\Bundle\AdminBundle\Service\Document\DocumentPayloadMapper;
use OpenDxp\Bundle\AdminBundle\Service\Document\DocumentPersistenceCoordinator;
use OpenDxp\Model\Document\Folder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class SaveFolderHandler
{
    public function __construct(
        private readonly DocumentPayloadMapper $mapper,
        private readonly DocumentPersistenceCoordinator $coordinator,
    ) {}

    public function __invoke(SaveFolderPayload $payload): SaveFolderResult
    {
        $folder = Folder::getById($payload->id);
        if (!$folder) {
            throw new NotFoundHttpException('Folder not found');
        }

        $this->mapper->applyFolderPayload($payload, $folder);
        $result = $this->coordinator->save($folder, 'publish');

        return new SaveFolderResult(
            folder: $result->document instanceof Folder ? $result->document : $folder,
            treeData: $result->treeData,
        );
    }
}
