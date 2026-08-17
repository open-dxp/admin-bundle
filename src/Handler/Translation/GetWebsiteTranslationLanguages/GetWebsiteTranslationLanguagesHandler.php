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

namespace OpenDxp\Bundle\AdminBundle\Handler\Translation\GetWebsiteTranslationLanguages;

use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;

final class GetWebsiteTranslationLanguagesHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(): GetWebsiteTranslationLanguagesResult
    {
        $user = $this->userContext->getAdminUser();

        return new GetWebsiteTranslationLanguagesResult(
            view: $user->getAllowedLanguagesForViewingWebsiteTranslations(),
            edit: $user->getAllowedLanguagesForEditingWebsiteTranslations(),
        );
    }
}
