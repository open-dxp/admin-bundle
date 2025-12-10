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

namespace OpenDxp\Bundle\AdminBundle\Translation;

use OpenDxp\Security\User\UserLoader;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class AdminUserTranslator implements TranslatorInterface, LocaleAwareInterface
{
    public function __construct(private readonly TranslatorInterface $translator, private readonly UserLoader $userLoader)
    {
    }

    private function getUserLocale(): ?string
    {
        if (($user = $this->userLoader->getUser()) instanceof \OpenDxp\Model\User) {
            return $user->getLanguage();
        }

        return null;
    }

    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        $domain ??= 'admin';
        $locale ??= $this->getUserLocale();

        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    public function setLocale(string $locale): void
    {
        if ($this->translator instanceof LocaleAwareInterface) {
            $this->translator->setLocale($locale);
        }
    }

    public function getLocale(): string
    {
        if ($this->translator instanceof LocaleAwareInterface) {
            return $this->translator->getLocale();
        }

        return \OpenDxp\Tool::getDefaultLanguage();
    }
}
