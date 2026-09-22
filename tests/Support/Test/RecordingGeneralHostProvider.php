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

namespace OpenDxp\Bundle\AdminBundle\Tests\Support\Test;

use OpenDxp\Http\Request\Host\GeneralHostProviderInterface;

final class RecordingGeneralHostProvider implements GeneralHostProviderInterface
{
    /**
     * @var list<array<string, mixed>>
     */
    public array $receivedContexts = [];

    public function __construct(private readonly string $host)
    {
    }

    public function provide(array $context = []): ?string
    {
        $this->receivedContexts[] = $context;

        return $this->host;
    }
}
