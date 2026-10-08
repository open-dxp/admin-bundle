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

namespace OpenDxp\Bundle\AdminBundle\Tests\Application\Host;

use OpenDxp\Http\Request\Host\GeneralHostProviderInterface;

/**
 * Provides one host and remembers each context it was asked with.
 */
final class MockGeneralHostProvider implements GeneralHostProviderInterface
{
    /**
     * @var list<array<string, mixed>>
     */
    public array $contexts = [];

    public function __construct(private readonly string $host)
    {
    }

    public function provide(array $context = []): ?string
    {
        $this->contexts[] = $context;

        return $this->host;
    }
}
