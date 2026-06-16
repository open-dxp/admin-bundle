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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings;

use OpenDxp\Localization\LocaleServiceInterface;

final class GetAvailableCountriesHandler
{
    public function __construct(private readonly LocaleServiceInterface $localeService)
    {
    }

    public function __invoke(): GetAvailableCountriesResult
    {
        $countries = $this->localeService->getDisplayRegions();
        asort($countries);

        $options = [];
        foreach ($countries as $short => $translation) {
            if (strlen((string) $short) === 2) {
                $options[] = ['key' => $translation . ' (' . $short . ')', 'value' => $short];
            }
        }

        return new GetAvailableCountriesResult(options: $options);
    }
}
