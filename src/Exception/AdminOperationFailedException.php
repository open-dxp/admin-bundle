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

namespace OpenDxp\Bundle\AdminBundle\Exception;

use Psr\Log\LogLevel;
use RuntimeException;
use Symfony\Component\HttpKernel\Attribute\WithLogLevel;

/**
 * Signals an expected, recoverable business-rule failure that the admin UI
 * handles locally via a `success:false` JSON body at HTTP 200.
 * Symfony's ErrorListener logs every exception, this one at warning level.
 * Some handlers wrap unexpected errors in it.
 *
 * @see \OpenDxp\Bundle\AdminBundle\EventListener\AdminExceptionListener::onKernelException()
 */
#[WithLogLevel(LogLevel::WARNING)]
final class AdminOperationFailedException extends RuntimeException
{
    /**
     * @param array<string, mixed> $extra additional keys merged into the JSON response body
     */
    public function __construct(
        string $message = '',
        private readonly array $extra = []
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtra(): array
    {
        return $this->extra;
    }
}
