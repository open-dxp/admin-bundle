<?php

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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\User\GetTokenLoginLink;

use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Generator\CustomLoginUrlGenerator;
use OpenDxp\Model\User;
use OpenDxp\Tool;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GetTokenLoginLinkHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly CustomLoginUrlGenerator $loginUrlGenerator,
        private readonly TranslatorInterface $translator,
    ) {}

    public function __invoke(GetTokenLoginLinkPayload $payload): GetTokenLoginLinkResult
    {
        $user = User::getById($payload->id);
        if (!$user) {
            throw new NotFoundHttpException($this->translator->trans('login_token_invalid_user_error', [], 'admin'));
        }

        $adminUser = $this->userContext->getAdminUser();
        if ($user->isAdmin() && !$adminUser?->isAdmin()) {
            throw new AccessDeniedHttpException($this->translator->trans('login_token_as_admin_non_admin_user_error', [], 'admin'));
        }

        if (empty($user->getPassword())) {
            throw new AccessDeniedHttpException($this->translator->trans('login_token_no_password_error', [], 'admin'));
        }

        $token = Tool\Authentication::generateTokenByUser($user);
        $link = $this->loginUrlGenerator->generate(['token' => $token]);

        return new GetTokenLoginLinkResult(link: $link);
    }
}
