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

namespace OpenDxp\Bundle\AdminBundle\Twig\Extension;

use Exception;
use OpenDxp\Bundle\AdminBundle\System\AdminConfig;
use OpenDxp\Bundle\AdminBundle\Tool;
use OpenDxp\Config;
use OpenDxp\Http\Request\Resolver\EditmodeResolver;
use OpenDxp\Security\User\UserLoader;
use OpenDxp\Tool\Admin;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @internal
 */
class AdminExtension extends AbstractExtension
{
    public function __construct(
        private UrlGeneratorInterface $generator,
        private EditmodeResolver $editmodeResolver,
        private UserLoader $userLoader
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('opendxp_language_flag', [$this, 'getLanguageFlagFile']),
            new TwigFunction('opendxp_minimize_scripts', [$this, 'minimize']),
            new TwigFunction('opendxp_editmode_admin_language', [$this, 'getAdminLanguage']),
            new TwigFunction('opendxp_login_background_image', [$this, 'getLoginBackgroundImage']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('opendxp_inline_icon', [$this, 'inlineIcon']),
            new TwigFilter('opendxp_lazy_icon', [$this, 'lazyIcon']),
            new TwigFilter('opendxp_twemoji_variant_icon', [$this, 'twemojiVariantIcon']),
        ];
    }

    public function getLanguageFlagFile(string $language, bool $absolutePath = true, bool $includeUnknown = true): string
    {
        return Tool::getLanguageFlagFile($language, $absolutePath, $includeUnknown);
    }

    public function getAdminLanguage(): ?string
    {
        $openDxpUser = null;
        if ($this->editmodeResolver->isEditmode()) {
            $openDxpUser = $this->userLoader->getUser();
        }

        return $openDxpUser?->getLanguage();
    }

    public function minimize(array $paths): string
    {
        $returnHtml = '';
        $scriptContents = '';
        foreach ($paths as $path) {
            $found = false;
            foreach ([
                OPENDXP_WEB_ROOT . '/bundles/opendxpadmin/js/' . $path,
                OPENDXP_WEB_ROOT . $path,
            ] as $fullPath) {
                if (is_file($fullPath)) {
                    $scriptContents .= file_get_contents($fullPath) . "\n\n\n";
                    $found = true;
                }
            }

            if (!$found) {
                $returnHtml .= $this->getScriptTag($path);
            }
        }

        $parameters = Admin::getMinimizedScriptPath($scriptContents);
        $url = $this->generator->generate('opendxp_admin_misc_scriptproxy', $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);

        $returnHtml .= $this->getScriptTag($url);

        return $returnHtml;
    }

    private function getScriptTag(string $url): string
    {
        return '<script src="' . $url . '"></script>' . "\n";
    }

    public function getLoginBackgroundImage(string $overwrite = ''): string
    {
        $possibleDefaultImages = [
            '/bundles/opendxpadmin/img/login/opendxp-loginscreen-version2.svg',
        ];
        $backgroundImageUrl = $possibleDefaultImages[array_rand($possibleDefaultImages)];

        if (empty($overwrite) === false) {
            $backgroundImageUrl = $overwrite;
        }

        $customImage = AdminConfig::get()['branding']['login_screen_custom_image'];

        if (empty($customImage) === true) {
            return $backgroundImageUrl;
        }

        if (
            preg_match('@^https?://@', $customImage) === 1
            || is_file(OPENDXP_WEB_ROOT . '/var/assets' . $customImage) === true
            || is_file(OPENDXP_WEB_ROOT . $customImage) === true
        ) {
            return $customImage;
        }

        $assetSource = Config::getSystemConfiguration('assets')['frontend_prefixes']['source'];

        if (empty($assetSource) === false) {
            $url = sprintf('%s/%s', $assetSource, $customImage);

            try {
                // Check if the image exists
                getimagesize($url);

                return $url;
            } catch (Exception) {
                return $backgroundImageUrl;
            }
        }

        return $backgroundImageUrl;
    }

    public function inlineIcon(string $icon): string
    {
        $content = file_get_contents($icon);

        return sprintf(
            '<img src="data:%s;base64,%s" title="%s" data-imgpath="%s" />',
            mime_content_type($icon),
            base64_encode($content),
            basename($icon),
            str_replace(OPENDXP_WEB_ROOT, '', $icon)
        );
    }

    public function lazyIcon(string $icon): string
    {
        return sprintf(
            '<img src="%s" loading="lazy" class="lazy-load" title="%s" data-imgpath="%s" />',
            str_replace(OPENDXP_WEB_ROOT, '', $icon),
            basename($icon),
            str_replace(OPENDXP_WEB_ROOT, '', $icon)
        );
    }

    public function twemojiVariantIcon(string $icon): string
    {
        return sprintf(
            '<img title="%s" data-imgpath="%s" />',
            basename($icon),
            str_replace(OPENDXP_WEB_ROOT, '', $icon)
        );
    }
}
