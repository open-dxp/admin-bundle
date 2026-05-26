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

namespace OpenDxp\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator;

use OpenDxp\Bundle\AdminBundle\DataObject\GridColumnConfig\ConfigElementInterface;
use OpenDxp\Tool;
use stdClass;

abstract class AbstractOperator implements OperatorInterface
{
    protected string $label;

    /**
     * @var ConfigElementInterface[]
     */
    protected array $children;

    public function __construct(
        stdClass $config,
        protected array $context = []
    ) {
        $this->label = $config->label;
        $this->children = $config->children;
    }

    /**
     * @return ConfigElementInterface[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function expandLocales(): bool
    {
        return false;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * @return string[]
     */
    public function getValidLanguages(): array
    {
        return Tool::getValidLanguages();
    }

    public function getRenderer(): ?string
    {
        return null;
    }
}
