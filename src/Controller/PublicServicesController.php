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

namespace OpenDxp\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class PublicServicesController extends AbstractController
{
    public function customAdminEntryPointAction(Request $request): RedirectResponse
    {
        $params = $request->query->all();

        $url = match (true) {
            isset($params['token'])    => $this->generateUrl('opendxp_admin_login_check', $params),
            isset($params['deeplink']) => $this->generateUrl('opendxp_admin_login_deeplink', $params),
            default                    => $this->generateUrl('opendxp_admin_login', $params)
        };

        $redirect = new RedirectResponse($url);

        $customAdminPathIdentifier = $this->getParameter('opendxp_admin.custom_admin_path_identifier');
        if (!empty($customAdminPathIdentifier) && $request->cookies->get('opendxp_custom_admin') != $customAdminPathIdentifier) {
            $redirect->headers->setCookie(new Cookie('opendxp_custom_admin', $customAdminPathIdentifier, strtotime('+1 year')));
        }

        return $redirect;
    }
}
