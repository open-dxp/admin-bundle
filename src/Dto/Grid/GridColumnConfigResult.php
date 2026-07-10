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

final class GridColumnConfigResult implements \JsonSerializable
{
    public function __construct(
        public readonly array $availableFields = [],
        public readonly array $settings = [],
        public readonly array $availableConfigs = [],
        public readonly array $sharedConfigs = [],
        public readonly mixed $sortinfo = false,
        public readonly bool $onlyDirectChildren = false,
        public readonly mixed $pageSize = false,
        public readonly mixed $context = null,
        // asset-only
        public readonly ?bool $onlyUnreferenced = null,
        // dataobject-only
        public readonly ?string $language = null,
        public readonly ?string $searchFilter = null,
        public readonly ?array $filter = null,
    ) {}

    public function jsonSerialize(): array
    {
        $data = [
            'sortinfo' => $this->sortinfo,
            'availableFields' => $this->availableFields,
            'settings' => $this->settings,
            'onlyDirectChildren' => $this->onlyDirectChildren,
            'pageSize' => $this->pageSize,
            'availableConfigs' => $this->availableConfigs,
            'sharedConfigs' => $this->sharedConfigs,
            'context' => $this->context,
        ];

        if ($this->onlyUnreferenced !== null) {
            $data['onlyUnreferenced'] = $this->onlyUnreferenced;
        }
        if ($this->language !== null) {
            $data['language'] = $this->language;
        }
        if ($this->searchFilter !== null) {
            $data['searchFilter'] = $this->searchFilter;
        }
        if ($this->filter !== null) {
            $data['filter'] = $this->filter;
        }

        return $data;
    }
}
