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

namespace OpenDxp\Bundle\AdminBundle\DataObject\GridColumnConfig;

use InvalidArgumentException;
use OpenDxp\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator\Factory\OperatorFactoryInterface;
use OpenDxp\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator\OperatorInterface;
use OpenDxp\Bundle\AdminBundle\DataObject\GridColumnConfig\Value\Factory\ValueFactoryInterface;
use OpenDxp\Bundle\AdminBundle\DataObject\GridColumnConfig\Value\ValueInterface;
use Psr\Container\ContainerInterface;
use stdClass;

/**
 * @internal
 */
final readonly class Service
{
    public function __construct(private ContainerInterface $operatorFactories, private ContainerInterface $valueFactories)
    {
    }

    /**
     * @param stdClass[] $jsonConfigs
     *
     * @return ConfigElementInterface[]
     */
    public function buildOutputDataConfig(array $jsonConfigs, array $context = []): array
    {
        return $this->doBuildConfig($jsonConfigs, [], $context);
    }

    /**
     * @param stdClass[] $jsonConfigs
     *
     * @return ConfigElementInterface[]
     */
    private function doBuildConfig(array $jsonConfigs, array $config, array $context = []): array
    {
        if ($jsonConfigs === []) {
            return $config;
        }

        foreach ($jsonConfigs as $configElement) {
            if ('value' === $configElement->type) {
                $config[] = $this->buildValue($configElement->class, $configElement, $context);
            } elseif ('operator' === $configElement->type) {
                if (!empty($configElement->children)) {
                    $configElement->children = $this->doBuildConfig($configElement->children, [], $context);
                }

                $operator = $this->buildOperator($configElement->class, $configElement, $context);
                if ($operator) {
                    $config[] = $operator;
                }
            }
        }

        return $config;
    }

    private function buildOperator(string $name, stdClass $configElement, array $context = []): ?OperatorInterface
    {
        if (!$this->operatorFactories->has($name)) {
            throw new InvalidArgumentException(sprintf('Operator "%s" is not supported', $name));
        }

        /** @var OperatorFactoryInterface $factory */
        $factory = $this->operatorFactories->get($name);

        return $factory->build($configElement, $context);
    }

    private function buildValue(string $name, stdClass $configElement, mixed $context = null): ValueInterface
    {
        if (!$this->valueFactories->has($name)) {
            throw new InvalidArgumentException(sprintf('Value "%s" is not supported', $name));
        }

        /** @var ValueFactoryInterface $factory */
        $factory = $this->valueFactories->get($name);

        return $factory->build($configElement, $context);
    }
}
