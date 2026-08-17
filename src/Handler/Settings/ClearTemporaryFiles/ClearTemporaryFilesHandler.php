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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings\ClearTemporaryFiles;

use OpenDxp\Db;
use OpenDxp\Event\SystemEvents;
use OpenDxp\Helper\FileSystemHelper;
use OpenDxp\Tool;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

final class ClearTemporaryFilesHandler
{
    public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    public function __invoke(): void
    {
        // public files
        Tool\Storage::get('thumbnail')->deleteDirectory('/');
        Db::get()->executeStatement('TRUNCATE TABLE assets_image_thumbnail_cache');

        Tool\Storage::get('asset_cache')->deleteDirectory('/');

        // system files
        FileSystemHelper::recursiveDelete(OPENDXP_SYSTEM_TEMP_DIRECTORY, false);

        $this->eventDispatcher->dispatch(new GenericEvent(), SystemEvents::CACHE_CLEAR_TEMPORARY_FILES);
    }
}
