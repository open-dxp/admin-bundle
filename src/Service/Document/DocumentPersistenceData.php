<?php

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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Service\Document;

final class DocumentPersistenceData
{
    /**
     * @param array{versionDate: int, versionCount: int} $data
     * @param ?array{id: int, modificationDate: int, isAutoSave: bool} $draft
     */
    public function __construct(
        public readonly array $data,
        public readonly array $treeData,
        public readonly ?array $draft = null,
    ) {}
}
