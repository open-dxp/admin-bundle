<?php

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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * The id of a zip download or a zip import names a file in the temporary directory.
 * The handlers create it with uniqid(), so it consists of hex digits only.
 */
final class ZipJobId
{
    /**
     * @psalm-taint-escape file
     */
    public static function fromRequest(string $jobId): string
    {
        if (preg_match('/^[0-9a-f]+$/', $jobId) !== 1) {
            throw new BadRequestHttpException('Invalid job id');
        }

        return $jobId;
    }
}
