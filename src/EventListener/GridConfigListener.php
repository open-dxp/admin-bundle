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

namespace OpenDxp\Bundle\AdminBundle\EventListener;

use OpenDxp\Db;
use OpenDxp\Event\DataObjectClassDefinitionEvents;
use OpenDxp\Event\DataObjectEvents;
use OpenDxp\Event\Model\DataObject\ClassDefinitionEvent;
use OpenDxp\Event\Model\DataObjectEvent;
use OpenDxp\Event\Model\UserRoleEvent;
use OpenDxp\Event\UserRoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
class GridConfigListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectClassDefinitionEvents::POST_DELETE => 'onClassDelete',
            UserRoleEvents::POST_DELETE => 'onUserDelete',
            DataObjectEvents::POST_DELETE => 'onObjectDelete',
        ];
    }

    public function onObjectDelete(DataObjectEvent $event): void
    {
        $object = $event->getObject();
        $objectId = $object->getId();

        $this->cleanupGridConfigFavourites('objectId = ' . $objectId);
    }

    public function onClassDelete(ClassDefinitionEvent $event): void
    {
        $class = $event->getClassDefinition();
        $classId = $class->getId();

        // collect gridConfigs for that class id
        $db = Db::get();
        $gridConfigIds = $db->fetchFirstColumn('select id from gridconfigs where classId = ?', [$classId]);
        if ($gridConfigIds) {
            $db->executeQuery('delete from gridconfig_shares where gridConfigId in (' . implode('', $gridConfigIds) . ')');
        }

        $this->cleanupGridConfigs('classId = ' . $db->quote($classId));
        $this->cleanupGridConfigFavourites('classId = ' . $db->quote($classId));
    }

    public function onUserDelete(UserRoleEvent $event): void
    {
        $user = $event->getUserRole();
        $userId = $user->getId();

        $db = Db::get();

        $gridConfigIds = $db->fetchFirstColumn('select id from gridconfigs where ownerId = ' . $userId);
        if ($gridConfigIds) {
            $db->executeQuery('delete from gridconfig_shares where gridConfigId in (' . implode('', $gridConfigIds) . ')');
        }

        $this->cleanupGridConfigs('ownerId = ' . $userId);
        $this->cleanupGridConfigFavourites('ownerId = ' . $userId);
    }

    protected function cleanupGridConfigs(string $condition): void
    {
        $db = Db::get();
        $db->executeQuery('DELETE FROM gridconfigs where ' . $condition);
    }

    protected function cleanupGridConfigFavourites(string $condition): void
    {
        $db = Db::get();
        $db->executeQuery('DELETE FROM gridconfig_favourites where ' . $condition);
    }
}
