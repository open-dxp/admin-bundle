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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin;

use OpenDxp\Bundle\AdminBundle\Attribute\SessionIdentityAware;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Handler\Admin\Settings\SettingsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Admin\Settings\SettingsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Admin\Statistics\StatisticsHandler;
use OpenDxp\Controller\KernelResponseEventInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class IndexController extends AdminAbstractController implements KernelResponseEventInterface
{
    #[Route('/', name: 'opendxp_admin_index', methods: ['GET'])]
    #[SessionIdentityAware]
    public function indexAction(
        Request $request,
        SettingsPayload $payload,
        SettingsHandler $handler,
        TranslatorInterface $translator,
    ): Response {
        $user = $this->getAdminUser();

        if ($user->getTwoFactorAuthentication('required') && !$user->getTwoFactorAuthentication('enabled')) {
            return $this->redirectToRoute('opendxp_admin_2fa_setup');
        }

        $request->setLocale($user->getLanguage());
        if ($translator instanceof LocaleAwareInterface) {
            $translator->setLocale($user->getLanguage());
        }

        $result = $handler($payload);

        return $this->render($result->template ?? '@OpenDxpAdmin/admin/index/index.html.twig', $result->templateParams);
    }

    #[Route('/index/statistics', name: 'opendxp_admin_index_statistics', methods: ['GET'])]
    public function statisticsAction(
        Request $request,
        StatisticsHandler $handler,
    ): JsonResponse {

        if (!$request->isXmlHttpRequest()) {
            throw $this->createAccessDeniedHttpException();
        }

        $handler();

        return $this->apiOk();
    }

    public function onKernelResponseEvent(ResponseEvent $event): void
    {
        $event->getResponse()->headers->set('X-Frame-Options', 'deny', true);
    }
}
