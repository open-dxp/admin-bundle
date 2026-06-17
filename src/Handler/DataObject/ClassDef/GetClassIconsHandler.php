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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Helper\FileSystemHelper;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class GetClassIconsHandler
{
    public function __construct(private readonly EventDispatcherInterface $eventDispatcher) {}

    public function __invoke(GetClassIconsPayload $payload): GetClassIconsResult
    {
        $type = $payload->type;
        $classId = $payload->classId;

        if ($type === '') {
            return new GetClassIconsResult(icons: []);
        }

        $iconDir = OPENDXP_WEB_ROOT . '/bundles/opendxpadmin/img';

        if ($type === null) {
            $icons = [
                ...FileSystemHelper::scanDirectory($iconDir . '/object-icons/'),
                ...FileSystemHelper::scanDirectory($iconDir . '/flat-color-icons/'),
                ...FileSystemHelper::scanDirectory($iconDir . '/twemoji/'),
            ];
        } else {
            $icons = match ($type) {
                'color' => FileSystemHelper::scanDirectory($iconDir . '/flat-color-icons/'),
                'white' => FileSystemHelper::scanDirectory($iconDir . '/flat-white-icons/'),
                'twemoji-1', 'twemoji-2', 'twemoji-3',
                'twemoji_variants-1', 'twemoji_variants-2', 'twemoji_variants-3'
                    => FileSystemHelper::scanDirectory($iconDir . '/twemoji/'),
                default => [],
            };
        }

        $style = $type === 'white' ? 'background-color:#000' : '';

        foreach ($icons as &$icon) {
            $icon = str_replace(OPENDXP_WEB_ROOT, '', $icon);
        }

        $event = new GenericEvent(null, ['icons' => $icons, 'classId' => $classId]);
        $this->eventDispatcher->dispatch($event, AdminEvents::CLASS_OBJECT_ICONS_PRE_SEND_DATA);
        $icons = $event->getArgument('icons');

        $startIndex = 0;

        if ($type !== null && str_starts_with($type, 'twemoji')) {
            foreach ($icons as $index => $twemojiIcon) {
                $iconBase = basename($twemojiIcon);
                $explodeByHyphen = explode('-', $iconBase);

                if (
                    (!str_starts_with($type, 'twemoji_variants') && isset($explodeByHyphen[1])) ||
                    (str_starts_with($type, 'twemoji_variants') && !isset($explodeByHyphen[1]))
                ) {
                    unset($icons[$index]);
                }
            }

            $icons = array_values($icons);
            $limit = count($icons);

            if (str_ends_with($type, '-1')) {
                $limit = (int) floor($limit / 3);
            }
            if (str_ends_with($type, '-2')) {
                $startIndex = (int) floor($limit / 3);
                $limit = (int) floor($limit / 3 * 2);
            }
            if (str_ends_with($type, '-3')) {
                $startIndex = (int) floor($limit / 3 * 2);
            }
        } else {
            $limit = count($icons);
        }

        $result = [];
        for ($i = $startIndex; $i < $limit; $i++) {
            $icon = $icons[$i];
            $content = file_get_contents(OPENDXP_WEB_ROOT . $icon);
            $result[] = [
                'text' => sprintf(
                    '<img style="%s" src="data:%s;base64,%s"/>',
                    $style,
                    mime_content_type(OPENDXP_WEB_ROOT . $icon),
                    base64_encode($content)
                ),
                'value' => $icon,
            ];
        }

        return new GetClassIconsResult(icons: $result);
    }
}
