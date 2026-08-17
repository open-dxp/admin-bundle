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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Dto\Grid;

final readonly class DataObjectGridColumnConfig extends GridColumnConfig
{
    public function __construct(
        array $availableFields,
        array $settings,
        array $availableConfigs,
        array $sharedConfigs,
        mixed $sortinfo,
        bool $onlyDirectChildren,
        mixed $pageSize,
        mixed $context,
        public string $language,
        public string $searchFilter,
        public array $filter,
    ) {
        parent::__construct(
            availableFields: $availableFields,
            settings: $settings,
            availableConfigs: $availableConfigs,
            sharedConfigs: $sharedConfigs,
            sortinfo: $sortinfo,
            onlyDirectChildren: $onlyDirectChildren,
            pageSize: $pageSize,
            context: $context,
        );
    }

    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'language' => $this->language,
            'searchFilter' => $this->searchFilter,
            'filter' => $this->filter,
        ];
    }
}
