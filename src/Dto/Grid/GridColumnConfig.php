<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Dto\Grid;

/**
 * Grid column configuration resolved by a domain-specific resolver service
 * (AssetGridColumnConfigResolver, DataObjectGridColumnConfigResolver). This is a plain
 * service-layer data object, not a Handler's wire-facing Result.
 */
abstract readonly class GridColumnConfig
{
    public function __construct(
        public array $availableFields,
        public array $settings,
        public array $availableConfigs,
        public array $sharedConfigs,
        public mixed $sortinfo,
        public bool $onlyDirectChildren,
        public mixed $pageSize,
        public mixed $context,
    ) {
    }

    public function toArray(): array
    {
        return [
            'sortinfo' => $this->sortinfo,
            'availableFields' => $this->availableFields,
            'settings' => $this->settings,
            'onlyDirectChildren' => $this->onlyDirectChildren,
            'pageSize' => $this->pageSize,
            'availableConfigs' => $this->availableConfigs,
            'sharedConfigs' => $this->sharedConfigs,
            'context' => $this->context,
        ];
    }
}
