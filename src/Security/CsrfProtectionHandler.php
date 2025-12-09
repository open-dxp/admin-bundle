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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Security;

use OpenDxp\Tool\Session;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Twig\Environment;

/**
 * @internal
 */
class CsrfProtectionHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected ?string $csrfToken = null;

    public function __construct(protected array $excludedRoutes, protected Environment $twig)
    {
    }

    public function checkCsrfToken(Request $request): void
    {
        $csrfToken = $this->getCsrfToken($request->getSession());
        $requestCsrfToken = $request->headers->get('x_opendxp_csrf_token');
        if (!$requestCsrfToken) {
            $requestCsrfToken = $request->get('csrfToken');
        }

        if (!$csrfToken || $csrfToken !== $requestCsrfToken) {
            $this->logger->error('Detected CSRF attack on {request}', [
                'request' => $request->getPathInfo(),
            ]);

            throw new AccessDeniedHttpException('Possible CSRF Attack detected. Please try again.');
        }
    }

    public function getCsrfToken(SessionInterface $session): ?string
    {
        if (!$this->csrfToken) {
            $this->csrfToken = Session::getSessionBag($session)->get('csrfToken');
            if (!$this->csrfToken) {
                $this->regenerateCsrfToken($session, false);
            }
        }

        return $this->csrfToken;
    }

    public function regenerateCsrfToken(SessionInterface $session, bool $force = true): void
    {
        $this->csrfToken = Session::useBag($session, function (AttributeBagInterface $adminSession) use ($force) {
            if ($force || !$adminSession->get('csrfToken')) {
                $adminSession->set('csrfToken', sha1(generateRandomSymfonySecret()));
            }

            return $adminSession->get('csrfToken');
        });

        $this->twig->addGlobal('csrfToken', $this->csrfToken);
    }

    public function generateCsrfToken(SessionInterface $session): void
    {
        $this->twig->addGlobal('csrfToken', $this->getCsrfToken($session));
    }

    public function getExcludedRoutes(): array
    {
        return $this->excludedRoutes;
    }
}
