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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings\ThumbnailAdapterCheck;

use OpenDxp\Image;
use OpenDxp\Image\Adapter\GD;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ThumbnailAdapterCheckHandler
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function __invoke(): ThumbnailAdapterCheckResult
    {
        $content = '';

        if (Image::getInstance() instanceof GD) {
            $content = '<span style="color: red; font-weight: bold;padding: 10px;margin:0 0 20px 0;border:1px solid red;display:block;">' .
                $this->translator->trans('important_use_imagick_pecl_extensions_for_best_results_gd_is_just_a_fallback_with_less_quality', [], 'admin') .
                '</span>';
        }

        return new ThumbnailAdapterCheckResult(content: $content);
    }
}
