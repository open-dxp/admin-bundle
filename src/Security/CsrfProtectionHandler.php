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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Security;

use OpenDxp\Helper\StringHelper;
use OpenDxp\Tool\Session;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @internal
 */
class CsrfProtectionHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        protected array $excludedRoutes,
    ) {
    }

    public function checkCsrfToken(Request $request): void
    {
        $csrfToken = $this->getCsrfToken($request->getSession());
        $requestCsrfToken = $request->headers->get('x_opendxp_csrf_token');

        if (!$requestCsrfToken) {
            $requestCsrfToken = $request->query->has('csrfToken')
                ? $request->query->get('csrfToken')
                : $request->request->get('csrfToken');
        }

        if (!$csrfToken || $csrfToken !== $requestCsrfToken) {
            $this->logger->error('Detected CSRF attack on {request}', [
                'request' => $request->getPathInfo(),
            ]);

            throw new AccessDeniedHttpException('Possible CSRF Attack detected. Please try again.');
        }
    }

    /**
     * A service outlives the request in a long running process, so the token stays in the session.
     */
    public function getCsrfToken(SessionInterface $session): ?string
    {
        $token = Session::getSessionBag($session)?->get('csrfToken');

        if ($token) {
            return $token;
        }

        $this->regenerateCsrfToken($session, false);

        return Session::getSessionBag($session)?->get('csrfToken');
    }

    public function regenerateCsrfToken(SessionInterface $session, bool $force = true): void
    {
        Session::useBag($session, static function (AttributeBagInterface $adminSession) use ($force): void {
            if ($force || !$adminSession->get('csrfToken')) {
                $adminSession->set('csrfToken', sha1(StringHelper::generateRandomSymfonySecret()));
            }
        });
    }

    public function generateCsrfToken(SessionInterface $session): void
    {
        $this->regenerateCsrfToken($session, false);
    }

    public function getExcludedRoutes(): array
    {
        return $this->excludedRoutes;
    }
}
