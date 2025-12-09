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
use OpenDxp\Model\Element\ElementInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class TranslateValue extends AbstractOperator
{
    private string $prefix;

    /**
     * @var string|null
     */
    private mixed $locale = null;

    public function __construct(private readonly LocaleAwareInterface|\stdClass|TranslatorInterface $translator, \stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);
        $this->prefix = $config->prefix ?? '';
        if (isset($context['language'])) {
            $this->locale = $context['language'];
        }
    }

    public function getLabeledValue(array|ElementInterface $element): ResultContainer|\stdClass|null
    {
        $children = $this->getChildren();
        if (isset($children[0])) {
            $value = $children[0]->getLabeledValue($element);
            if ((string)$value->value !== '') {
                $currentLocale = $this->translator->getLocale();
                if (null != $this->locale) {
                    $this->translator->setLocale($this->locale);
                }

                $value->value = $this->translator->trans($this->prefix .$value->value, []);

                $this->translator->setLocale($currentLocale);
            }

            return $value;
        }

        return null;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }
}
