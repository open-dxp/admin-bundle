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

/**
 * @internal
 */
class AdminExtension
{
    public function __construct(
        private readonly UrlGeneratorInterface $generator,
        private readonly EditmodeResolver $editmodeResolver,
        private readonly UserLoader $userLoader
    ) {
    }

    #[\Twig\Attribute\AsTwigFunction('opendxp_language_flag')]
    public function getLanguageFlagFile(string $language, bool $absolutePath = true, bool $includeUnknown = true): string
    {
        return Tool::getLanguageFlagFile($language, $absolutePath, $includeUnknown);
    }

    #[\Twig\Attribute\AsTwigFunction('opendxp_editmode_admin_language')]
    public function getAdminLanguage(): ?string
    {
        $openDxpUser = null;
        if ($this->editmodeResolver->isEditmode()) {
            $openDxpUser = $this->userLoader->getUser();
        }

        return $openDxpUser?->getLanguage();
    }

    #[\Twig\Attribute\AsTwigFunction('opendxp_minimize_scripts')]
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

        return $returnHtml . $this->getScriptTag($url);
    }

    private function getScriptTag(string $url): string
    {
        return '<script src="' . $url . '"></script>' . "\n";
    }

    #[\Twig\Attribute\AsTwigFunction('opendxp_login_background_image')]
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

        if (empty($customImage)) {
            return $backgroundImageUrl;
        }

        if (
            preg_match('@^https?://@', $customImage) === 1
            || is_file(OPENDXP_WEB_ROOT . '/var/assets' . $customImage)
            || is_file(OPENDXP_WEB_ROOT . $customImage)
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

    #[\Twig\Attribute\AsTwigFilter('opendxp_inline_icon')]
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

    #[\Twig\Attribute\AsTwigFilter('opendxp_lazy_icon')]
    public function lazyIcon(string $icon): string
    {
        return sprintf(
            '<img src="%s" loading="lazy" class="lazy-load" title="%s" data-imgpath="%s" />',
            str_replace(OPENDXP_WEB_ROOT, '', $icon),
            basename($icon),
            str_replace(OPENDXP_WEB_ROOT, '', $icon)
        );
    }

    #[\Twig\Attribute\AsTwigFilter('opendxp_twemoji_variant_icon')]
    public function twemojiVariantIcon(string $icon): string
    {
        return sprintf(
            '<img title="%s" data-imgpath="%s" />',
            basename($icon),
            str_replace(OPENDXP_WEB_ROOT, '', $icon)
        );
    }
}
