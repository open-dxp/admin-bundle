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

namespace OpenDxp\Bundle\AdminBundle\Security\Authenticator;

use OpenDxp\Security\User\User;
use OpenDxp\Tool\Authentication;
use OpenDxp\Tool\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * @internal
 */
class AdminTokenAuthenticator extends AdminAbstractAuthenticator
{
    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === self::OPENDXP_ADMIN_LOGIN_CHECK
            && $request->get('token');
    }

    public function authenticate(Request $request): Passport
    {
        $openDxpUser = Authentication::authenticateToken($request->get('token'));

        if ($openDxpUser) {
            $openDxpUser->setTwoFactorAuthentication('required', false);

            $userBadge = new UserBadge($openDxpUser->getUsername(), fn () => new User($openDxpUser));

            if ($request->get('reset', false)) {
                // save the information to session when the user want's to reset the password
                // this is because otherwise the old password is required

                Session::useBag($request->getSession(), function (AttributeBagInterface $adminSession): void {
                    $adminSession->set('password_reset', true);
                });
            }

            return new SelfValidatingPassport($userBadge);
        }

        throw new AuthenticationException('Failed to authenticate with username and token');
    }
}
