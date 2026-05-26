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

use OpenDxp\Config;
use OpenDxp\Helper\StringHelper;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @internal
 */
class ContentSecurityPolicyHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ?string $nonce = null;

    private const string SELF = "'self'";

    public const string DEFAULT_OPT = 'default-src';

    public const string IMG_OPT = 'img-src';

    public const string SCRIPT_OPT = 'script-src';

    public const string STYLE_OPT = 'style-src';

    public const string CONNECT_OPT = 'connect-src';

    public const string FONT_OPT = 'font-src';

    public const string MEDIA_OPT = 'media-src';

    public const string FRAME_OPT = 'frame-src';

    public const string FRAME_ANCHESTORS = 'frame-ancestors';

    public const string WORKER_OPT = 'worker-src';

    private array $allowedUrls = [
        self::CONNECT_OPT => [
            'https://metrics.opendxp.io/', // AdminBundle statistics & update-check service
            'https://nominatim.openstreetmap.org/', // CoreBundle geocoding_url_template
        ],
        self::SCRIPT_OPT => [
            'https://buttons.github.io/buttons.js', // GitHub star button on login page
            'https://code.jquery.com/', // jQuery for the icon library
        ],
        self::FRAME_OPT => [
            'https://www.youtube-nocookie.com/', // Video preview thumbnail for YouTube
            'https://www.dailymotion.com/',      // Video preview thumbnail for Dailymotion
            'https://player.vimeo.com/',         // Video preview thumbnail for Vimeo
        ],
    ];

    public function __construct(
        protected Config $config,
        protected array $cspHeaderOptions = []
    ) {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->cspHeaderOptions = $resolver->resolve($cspHeaderOptions);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            self::DEFAULT_OPT => self::SELF,
            self::IMG_OPT => '* data: blob:',
            self::MEDIA_OPT => self::SELF . ' data:',
            self::SCRIPT_OPT => self::SELF . " 'nonce-" . $this->getNonce() . "' 'unsafe-eval'",
            self::STYLE_OPT => self::SELF . " 'unsafe-inline'",
            self::FRAME_OPT => self::SELF . ' data:',
            self::FRAME_ANCHESTORS => self::SELF,
            self::CONNECT_OPT => self::SELF . ' blob:',
            self::FONT_OPT => self::SELF,
            self::WORKER_OPT => self::SELF . ' blob:',
        ]);
    }

    public function getCspHeader(): string
    {
        $cspHeaderOptions = array_map(
            fn ($k, $v) => sprintf('%s %s %s', $k, $v, $this->getAllowedUrls($k)),
            array_keys($this->cspHeaderOptions),
            array_values($this->cspHeaderOptions)
        );

        return implode(';', $cspHeaderOptions);
    }

    private function getAllowedUrls(string $key): string
    {
        return isset($this->allowedUrls[$key]) && is_array($this->allowedUrls[$key]) ? implode(' ', $this->allowedUrls[$key]) : '';
    }

    /**
     * @return $this
     */
    public function addAllowedUrls(string $key, array $value): static
    {
        if (!isset($this->allowedUrls[$key])) {
            $this->allowedUrls[$key] = [];
        }

        foreach ($value as $val) {
            $this->allowedUrls[$key][] = $val;
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function setCspHeader(string $key, string $value): static
    {
        $this->cspHeaderOptions[$key] = $value;

        return $this;
    }

    public function getNonceHtmlAttribute(): string
    {
        return $this->config['admin_csp_header']['enabled'] ? ' nonce="' . $this->getNonce() . '"' : '';
    }

    /**
     * Generates a random nonce parameter.
     */
    private function getNonce(): string
    {
        if (!$this->nonce) {
            $this->nonce = StringHelper::generateRandomSymfonySecret();
        }

        return $this->nonce;
    }
}
