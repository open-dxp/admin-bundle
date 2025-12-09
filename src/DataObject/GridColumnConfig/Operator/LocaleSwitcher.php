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

namespace OpenDxp\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator;

use OpenDxp\Bundle\AdminBundle\DataObject\GridColumnConfig\ResultContainer;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Model\Element\ElementInterface;
use stdClass;

/**
 * @internal
 */
final class LocaleSwitcher extends AbstractOperator
{
    private readonly ?string $locale;

    public function __construct(
        private readonly LocaleServiceInterface $localeService,
        stdClass $config,
        array $context = []
    ) {
        parent::__construct($config, $context);
        $this->locale = $config->locale ?? null;
    }

    public function getLabeledValue(array|ElementInterface $element): ResultContainer|stdClass|null
    {
        $result = new stdClass();
        $result->label = $this->label;

        $children = $this->getChildren();

        if (!$children) {
            return $result;
        }
        $c = $children[0];
        $currentLocale = $this->localeService->getLocale();
        $this->localeService->setLocale($this->locale);
        $result = $c->getLabeledValue($element);
        $this->localeService->setLocale($currentLocale);

        return $result;
    }
}
