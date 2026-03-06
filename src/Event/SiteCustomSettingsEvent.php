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

namespace OpenDxp\Bundle\AdminBundle\Event;

use OpenDxp\Bundle\AdminBundle\Enum\SiteCustomConfigNodeType;
use OpenDxp\Model\Site;
use Symfony\Contracts\EventDispatcher\Event;

class SiteCustomSettingsEvent extends Event
{
    private array $configNodes = [];

    public function __construct(private readonly Site $site)
    {
    }

    public function addConfigNode(SiteCustomConfigNodeType $type, string $scope, string $name, string $label, array $config): void
    {
        if (!array_key_exists($scope, $this->configNodes)) {
            $this->configNodes[$scope] = [];
        }

        $this->configNodes[$scope][] = [
            'type'   => $type->value,
            'name'   => $name,
            'label'  => $label,
            'config' => $config,
        ];
    }

    public function getConfigNodes(): array
    {
        return $this->configNodes;
    }

    public function getSite(): Site
    {
        return $this->site;
    }
}
