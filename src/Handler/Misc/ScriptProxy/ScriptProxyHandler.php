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

namespace OpenDxp\Bundle\AdminBundle\Handler\Misc\ScriptProxy;

use InvalidArgumentException;
use OpenDxp\Tool\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ScriptProxyHandler
{
    public function __invoke(ScriptProxyPayload $payload): ScriptProxyResult
    {
        if (!$payload->storageFile) {
            throw new InvalidArgumentException('The parameter storageFile is required');
        }

        $fileExtension = pathinfo($payload->storageFile, PATHINFO_EXTENSION);
        $storage = Storage::get('admin');
        $scriptsContent = $storage->read($payload->storageFile);

        if (empty($scriptsContent)) {
            throw new NotFoundHttpException('Scripts not found');
        }

        $contentType = $fileExtension === 'css' ? 'text/css' : 'text/javascript';

        return new ScriptProxyResult(
            content: $scriptsContent,
            contentType: $contentType,
        );
    }
}
