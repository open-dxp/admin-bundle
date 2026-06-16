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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Folder;

use OpenDxp\Bundle\AdminBundle\Normalizer\ElementResponseNormalizer;
use OpenDxp\Model\Document\Folder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetFolderDataHandler
{
    public function __construct(private readonly ElementResponseNormalizer $normalizer) {}

    public function __invoke(int $id): GetFolderDataResult
    {
        $folder = Folder::getById($id);
        if (!$folder) {
            throw new NotFoundHttpException('Folder not found');
        }

        $folder = clone $folder;
        $folder->setParent(null);

        $data = $folder->getObjectVars();
        $data['locked'] = $folder->isLocked();

        $this->normalizer->normalize($folder, $data, self::class);

        return new GetFolderDataResult(folder: $folder, data: $data);
    }
}
