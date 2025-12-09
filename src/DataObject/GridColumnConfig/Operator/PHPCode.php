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

use Exception;
use OpenDxp\Bundle\AdminBundle\DataObject\GridColumnConfig\ResultContainer;
use OpenDxp\Model\Element\ElementInterface;
use Override;
use stdClass;

/**
 * @internal
 */
final class PHPCode extends AbstractOperator
{
    private readonly stdClass $config;

    private string $phpClass;

    private ?OperatorInterface $instance = null;

    public function __construct(stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->config = $config;
        $this->phpClass = $config->phpClass ?? '';
    }

    public function getPhpClass(): string
    {
        return $this->phpClass;
    }

    public function setPhpClass(string $phpClass): void
    {
        $this->phpClass = $phpClass;
        $this->instance = null;
    }

    #[Override]
    public function getLabel(): string
    {
        return $this->getInstance()->getLabel();
    }

    public function getLabeledValue(array|ElementInterface $element): ResultContainer|stdClass|null
    {
        try {
            return $this->getInstance()->getLabeledValue($element);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * @throws Exception
     */
    private function getInstance(): OperatorInterface
    {
        if (!$this->instance instanceof \OpenDxp\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator\OperatorInterface) {
            $this->instance = $this->buildInstance();
        }

        return $this->instance;
    }

    /**
     * @throws Exception
     */
    private function buildInstance(): OperatorInterface
    {
        $phpClass = $this->getPhpClass();

        if ($phpClass && class_exists($phpClass)) {
            return new $phpClass($this->config, $this->context);
        }

        throw new Exception('PHPCode operator class does not exist: ' . $phpClass);
    }
}
