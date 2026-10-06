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

namespace OpenDxp\Bundle\AdminBundle\Security;

use OpenDxp\Http\Request\Resolver\OpenDxpContextResolver;
use Stringable;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class CurrentCsrfToken implements Stringable
{
    public function __construct(
        private CsrfProtectionHandler $csrfProtection,
        private RequestStack $requestStack,
        private OpenDxpContextResolver $contextResolver,
    ) {
    }

    public function __toString(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null || !$request->hasSession()) {
            return '';
        }

        if (!$this->contextResolver->matchesOpenDxpContext($request, OpenDxpContextResolver::CONTEXT_ADMIN)) {
            trigger_deprecation(
                'open-dxp/admin-bundle',
                '1.5',
                'Printing the Twig global "csrfToken" outside the admin is deprecated and will throw an exception in 2.0.',
            );

            return '';
        }

        return $this->csrfProtection->getCsrfToken($request->getSession()) ?? '';
    }
}
