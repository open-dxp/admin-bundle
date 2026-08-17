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

namespace OpenDxp\Bundle\AdminBundle\Handler\Misc\GetCountryList;

use OpenDxp\Localization\LocaleServiceInterface;

final class GetCountryListHandler
{
    public function __construct(
        private readonly LocaleServiceInterface $localeService,
    ) {}

    public function __invoke(): GetCountryListResult
    {
        $countries = $this->localeService->getDisplayRegions();
        asort($countries);

        $data = [];
        foreach ($countries as $short => $translation) {
            if (strlen($short) === 2) {
                $data[] = [
                    'name' => $translation,
                    'code' => $short,
                ];
            }
        }

        return new GetCountryListResult(data: $data);
    }
}
