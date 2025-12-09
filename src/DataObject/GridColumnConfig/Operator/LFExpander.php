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
use OpenDxp\Tool;

/**
 * @internal
 */
final class LFExpander extends AbstractOperator
{
    /**
     * @var string[]
     */
    private readonly array $locales;

    private bool $asArray;

    public function __construct(private readonly LocaleServiceInterface $localeService, \stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->locales = $config->locales ?? [];
        $this->asArray = $config->asArray ?? false;
    }

    public function getLabeledValue(array|ElementInterface $element): ResultContainer|\stdClass|null
    {
        $children = $this->getChildren();
        if (isset($children[0])) {
            if ($this->getAsArray()) {
                $result = new ResultContainer();
                $result->label = $this->label;
                $resultValues = [];

                $currentLocale = $this->localeService->getLocale();

                $validLanguages = $this->getValidLanguages();
                foreach ($validLanguages as $validLanguage) {
                    $this->localeService->setLocale($validLanguage);

                    $childValue = $children[0]->getLabeledValue($element);
                    $resultValues[] = $childValue && $childValue->value ? $childValue : null;
                }

                $this->localeService->setLocale($currentLocale);

                $result->value = $resultValues;

                return $result;
            }

            return $children[0]->getLabeledValue($element);
        }

        return null;
    }

    #[\Override]
    public function expandLocales(): bool
    {
        return true;
    }

    /**
     * @return string[]
     */
    #[\Override]
    public function getValidLanguages(): array
    {
        if ($this->locales) {
            return $this->locales;
        }

        return Tool::getValidLanguages();
    }

    public function getAsArray(): bool
    {
        return $this->asArray;
    }

    public function setAsArray(bool $asArray): void
    {
        $this->asArray = $asArray;
    }
}
