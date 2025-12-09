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

namespace OpenDxp\Bundle\AdminBundle\DataObject\GridColumnConfig\Value\Factory;

use OpenDxp\Bundle\AdminBundle\DataObject\GridColumnConfig\Value\ValueInterface;
use OpenDxp\Localization\LocaleServiceInterface;

final readonly class DefaultValueFactory implements ValueFactoryInterface
{
    public function __construct(private string $className, private LocaleServiceInterface $localeService)
    {
    }

    public function build(\stdClass $configElement, mixed $context = null): ValueInterface
    {
        $value = new $this->className($configElement, $context);

        if (method_exists($value, 'setLocaleService')) {
            $value->setLocaleService($this->localeService);
        }

        return $value;
    }
}
